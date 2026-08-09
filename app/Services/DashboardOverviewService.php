<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregate counts for the Dashboard Overview widget (SRS 20.1 / D.01).
 */
class DashboardOverviewService
{
    /**
     * @return array{posts: int, pages: int, media: int, users: int}
     */
    public function counts(): array
    {
        return [
            'posts' => $this->postCount(),
            'pages' => $this->pageCount(),
            'media' => $this->mediaCount(),
            'users' => $this->userCount(),
        ];
    }

    public function postCount(): int
    {
        if (! Schema::hasTable('posts')) {
            return 0;
        }

        // Soft-deleted posts are excluded by default.
        return Post::query()->count();
    }

    public function pageCount(): int
    {
        if (! Schema::hasTable('pages')) {
            return 0;
        }

        return Page::query()->count();
    }

    public function mediaCount(): int
    {
        if (! Schema::hasTable('media_assets')) {
            return 0;
        }

        return MediaAsset::query()->count();
    }

    public function userCount(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        // Soft-deleted users are excluded; pending/suspended still count as system users.
        return User::query()->count();
    }
}
