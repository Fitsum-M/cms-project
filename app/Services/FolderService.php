<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FolderService
{
    /**
     * @param  array{name: string, parent_id?: int|null}  $data
     */
    public function create(array $data): Folder
    {
        $parentId = $data['parent_id'] ?? null;
        $name = $this->normalizeName($data['name']);

        $this->assertParentExists($parentId);
        $this->assertUniqueName($name, $parentId);

        return Folder::query()->create([
            'name' => $name,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * @param  array{name?: string, parent_id?: int|null}  $data
     */
    public function update(Folder $folder, array $data): Folder
    {
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $folder->parent_id;
        $parentId = $parentId !== null ? (int) $parentId : null;
        $name = array_key_exists('name', $data)
            ? $this->normalizeName((string) $data['name'])
            : $folder->name;

        $this->assertParentExists($parentId);

        if ($folder->wouldCreateCycle($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A folder cannot be its own parent or descendant.',
            ]);
        }

        $this->assertUniqueName($name, $parentId, $folder->id);

        $folder->fill([
            'name' => $name,
            'parent_id' => $parentId,
        ])->save();

        return $folder->refresh();
    }

    public function move(Folder $folder, ?int $newParentId): Folder
    {
        return $this->update($folder, ['parent_id' => $newParentId]);
    }

    /**
     * @param  list<int>  $mediaAssetIds
     */
    public function moveMedia(array $mediaAssetIds, ?int $folderId): int
    {
        if ($folderId !== null && ! Folder::query()->whereKey($folderId)->exists()) {
            throw ValidationException::withMessages([
                'folder_id' => 'Selected folder does not exist.',
            ]);
        }

        $ids = array_values(array_unique(array_map('intval', $mediaAssetIds)));

        if ($ids === []) {
            return 0;
        }

        return MediaAsset::query()
            ->whereIn('id', $ids)
            ->update(['folder_id' => $folderId]);
    }

    /**
     * Delete a folder. Empty folders delete immediately. Non-empty require $recursive.
     * Recursive: children deleted depth-first; media moved to root (null folder).
     */
    public function delete(Folder $folder, bool $recursive = false): void
    {
        if (! $folder->isEmpty() && ! $recursive) {
            throw ValidationException::withMessages([
                'folder' => 'Folder is not empty. Confirm recursive deletion to remove nested folders and unfile media.',
            ]);
        }

        DB::transaction(function () use ($folder): void {
            $this->deleteRecursive($folder);
        });
    }

    /**
     * @return array<int, string>
     */
    public function parentOptions(?int $excludeFolderId = null): array
    {
        $query = Folder::query()->orderBy('name');

        $excludeIds = [];
        if ($excludeFolderId !== null) {
            $excludeIds[] = $excludeFolderId;
            $folder = Folder::query()->find($excludeFolderId);
            if ($folder) {
                $excludeIds = [...$excludeIds, ...$folder->descendantIds()];
            }
        }

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (Folder $folder): array => [$folder->id => $this->hierarchicalLabel($folder)])
            ->all();
    }

    /**
     * Flat options for selects (upload destination, move target, settings).
     *
     * @return array<int, string>
     */
    public function options(): array
    {
        return Folder::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (Folder $folder): array => [$folder->id => $this->hierarchicalLabel($folder)])
            ->all();
    }

    /**
     * Nested tree for drag-and-drop UI.
     *
     * @return list<array{id: int, name: string, parent_id: int|null, media_count: int, children: list<array<string, mixed>>}>
     */
    public function tree(): array
    {
        $folders = Folder::query()
            ->withCount('mediaAssets')
            ->orderBy('name')
            ->get();

        /** @var array<int|string, list<Folder>> $byParent */
        $byParent = $folders->groupBy(fn (Folder $folder): string => (string) ($folder->parent_id ?? 'root'));

        $build = function (string $parentKey) use (&$build, $byParent): array {
            $nodes = [];

            foreach ($byParent[$parentKey] ?? [] as $folder) {
                $nodes[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'media_count' => (int) $folder->media_assets_count,
                    'children' => $build((string) $folder->id),
                ];
            }

            return $nodes;
        };

        return $build('root');
    }

    public function hierarchicalLabel(Folder $folder): string
    {
        $parts = [$folder->name];
        $current = $folder;

        while ($current->parent_id !== null) {
            $current = $current->parent()->first();
            if ($current === null) {
                break;
            }
            array_unshift($parts, $current->name);
        }

        return implode(' / ', $parts);
    }

    private function deleteRecursive(Folder $folder): void
    {
        $children = Folder::query()->where('parent_id', $folder->id)->get();

        foreach ($children as $child) {
            $this->deleteRecursive($child);
        }

        MediaAsset::query()
            ->where('folder_id', $folder->id)
            ->update(['folder_id' => null]);

        if (! $folder->delete()) {
            throw new RuntimeException('Failed to delete folder.');
        }
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Folder name is required.',
            ]);
        }

        if (mb_strlen($name) > 255) {
            throw ValidationException::withMessages([
                'name' => 'Folder name may not be greater than 255 characters.',
            ]);
        }

        return $name;
    }

    private function assertParentExists(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if (! Folder::query()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Selected parent folder does not exist.',
            ]);
        }
    }

    private function assertUniqueName(string $name, ?int $parentId, ?int $ignoreId = null): void
    {
        $query = Folder::query()
            ->where('name', $name)
            ->when(
                $parentId === null,
                fn ($q) => $q->whereNull('parent_id'),
                fn ($q) => $q->where('parent_id', $parentId),
            );

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A folder with this name already exists in the same parent folder.',
            ]);
        }
    }
}
