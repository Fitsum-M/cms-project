<?php

namespace App\Filament\Navigation;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Folders\FolderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Folder;
use App\Models\Page;

/**
 * Shared admin breadcrumb helpers (Review Comment #3 §6.2).
 */
final class AdminBreadcrumbs
{
    /**
     * Prepend Dashboard and, when a page has no trail of its own, append its title.
     *
     * @param  array<int|string, string>  $breadcrumbs
     * @return array<int|string, string>
     */
    public static function forPage(object $page, array $breadcrumbs, mixed $heading = null): array
    {
        if ($page instanceof Dashboard) {
            return [];
        }

        $dashboardUrl = Dashboard::getUrl();
        $dashboardLabel = Dashboard::getNavigationLabel() ?: 'Dashboard';

        if (! array_key_exists($dashboardUrl, $breadcrumbs)) {
            $breadcrumbs = [
                $dashboardUrl => $dashboardLabel,
                ...$breadcrumbs,
            ];
        }

        if (count($breadcrumbs) === 1) {
            $title = is_string($heading) && $heading !== ''
                ? $heading
                : (method_exists($page, 'getTitle') ? (string) $page->getTitle() : null);

            if (filled($title)) {
                $breadcrumbs[] = $title;
            }
        }

        return $breadcrumbs;
    }

    /**
     * @param  array<int|string, string>  $breadcrumbs
     * @param  array<string, string>  $insert
     * @return array<int|string, string>
     */
    public static function insertAfter(array $breadcrumbs, string $afterUrl, array $insert): array
    {
        if ($insert === []) {
            return $breadcrumbs;
        }

        $rebuilt = [];
        $inserted = false;

        foreach ($breadcrumbs as $key => $label) {
            $rebuilt[$key] = $label;

            if (! $inserted && (string) $key === $afterUrl) {
                foreach ($insert as $insertKey => $insertLabel) {
                    $rebuilt[$insertKey] = $insertLabel;
                }

                $inserted = true;
            }
        }

        return $inserted ? $rebuilt : [...$insert, ...$breadcrumbs];
    }

    /**
     * Parent pages, root first, linking to their edit screens.
     *
     * @return array<string, string>
     */
    public static function pageAncestors(Page $page): array
    {
        $chain = [];
        $current = $page->parent;
        $guard = 0;

        while ($current instanceof Page && $guard++ < 20) {
            $chain[] = $current;
            $current = $current->parent;
        }

        $crumbs = [];

        foreach (array_reverse($chain) as $ancestor) {
            $crumbs[PageResource::getUrl('edit', ['record' => $ancestor])] = $ancestor->title;
        }

        return $crumbs;
    }

    /**
     * Parent folders, root first, linking to their edit screens.
     *
     * @return array<string, string>
     */
    public static function folderAncestors(Folder $folder): array
    {
        $chain = [];
        $current = $folder->parent;
        $guard = 0;

        while ($current instanceof Folder && $guard++ < 20) {
            $chain[] = $current;
            $current = $current->parent;
        }

        $crumbs = [];

        foreach (array_reverse($chain) as $ancestor) {
            $crumbs[FolderResource::getUrl('edit', ['record' => $ancestor])] = $ancestor->name;
        }

        return $crumbs;
    }
}
