<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendContentService;
use App\Support\CustomFields\CustomFieldRegistry;
use App\Support\PostTypeRegistry;
use App\Support\Settings\GeneralSettings;
use Illuminate\View\View;

class CustomPostTypeController extends Controller
{
    public function index(
        string $postType,
        FrontendContentService $content,
        GeneralSettings $settings,
    ): View {
        abort_unless(CustomFieldRegistry::hasSchema($postType), 404);
        abort_unless(PostTypeRegistry::isRegistered($postType), 404);

        return view('frontend.type-index', [
            'siteTitle' => $settings->siteTitle(),
            'tagline' => $settings->tagline(),
            'postType' => $postType,
            'typeLabel' => PostTypeRegistry::label($postType),
            'posts' => $content->paginatedPostsByType($postType),
            'navPages' => $content->navigationPages(),
        ]);
    }
}
