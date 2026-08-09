<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MediaDeletionService
{
    public function __construct(
        private readonly MediaReferenceService $references,
    ) {}

    public function assertDeletable(MediaAsset $asset): void
    {
        $refs = $this->references->references($asset);

        if ($refs->isEmpty()) {
            return;
        }

        $list = $refs
            ->map(fn ($ref): string => "• {$ref->label} — {$ref->detail}")
            ->implode("\n");

        throw ValidationException::withMessages([
            'media' => "This media item is in use and cannot be deleted until references are removed:\n{$list}",
        ]);
    }

    public function delete(MediaAsset $asset): void
    {
        $this->assertDeletable($asset);
        $this->destroy($asset);
    }

    /**
     * Administrators may force-delete: clear references (empty placeholders) then remove the asset.
     */
    public function forceDelete(MediaAsset $asset): void
    {
        DB::transaction(function () use ($asset): void {
            $this->references->clearAll($asset);
            $this->destroy($asset);
        });
    }

    private function destroy(MediaAsset $asset): void
    {
        $asset->clearMediaCollection(MediaAsset::LIBRARY_COLLECTION);

        if (! $asset->delete()) {
            throw new RuntimeException('Failed to delete media asset.');
        }
    }
}
