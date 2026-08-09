<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Pages\Dam\UploadMedia;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Widgets\Widget;

/**
 * Dashboard Quick Actions — Add Post / Add Page / Upload Media (SRS 10.3, D.04).
 */
class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.quick-actions';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user?->can(Permission::DashboardView->value)) {
            return false;
        }

        return $user->can(Permission::PostsCreate->value)
            || $user->can(Permission::PagesCreate->value)
            || $user->can(Permission::MediaUpload->value);
    }

    /**
     * @return array{actions: list<array{label: string, url: string, description: string, icon: string}>}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $actions = [];

        if ($user?->can(Permission::PostsCreate->value)) {
            $actions[] = [
                'label' => 'Add New Post',
                'description' => 'Create a draft post',
                'url' => PostResource::getUrl('create'),
                'icon' => 'document-text',
            ];
        }

        if ($user?->can(Permission::PagesCreate->value)) {
            $actions[] = [
                'label' => 'Add New Page',
                'description' => 'Create a new page',
                'url' => PageResource::getUrl('create'),
                'icon' => 'document-duplicate',
            ];
        }

        if ($user?->can(Permission::MediaUpload->value)) {
            $actions[] = [
                'label' => 'Upload Media',
                'description' => 'Add files to the library',
                'url' => UploadMedia::getUrl(),
                'icon' => 'arrow-up-tray',
            ];
        }

        return [
            'actions' => $actions,
        ];
    }
}
