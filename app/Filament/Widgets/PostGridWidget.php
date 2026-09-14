<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Models\Post;
use App\Services\FrontendContentService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Dashboard visual grid of published posts (Review Comment #3 §8.1).
 */
class PostGridWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.post-grid';

    public static function canView(): bool
    {
        return auth()->user()?->can(Permission::DashboardView->value) ?? false;
    }

    /**
     * @return array{posts: Collection<int, Post>}
     */
    protected function getViewData(): array
    {
        return [
            'posts' => app(FrontendContentService::class)
                ->publishedPostsQuery()
                ->limit(6)
                ->get(),
        ];
    }
}
