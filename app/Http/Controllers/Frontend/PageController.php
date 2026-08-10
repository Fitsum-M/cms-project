<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendContentService;
use App\Support\Settings\GeneralSettings;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, FrontendContentService $content, GeneralSettings $settings): View
    {
        $page = $content->findPublicPage($slug);

        abort_if($page === null, 404);

        return view('frontend.page', [
            'siteTitle' => $settings->siteTitle(),
            'tagline' => $settings->tagline(),
            'page' => $page,
            'navPages' => $content->navigationPages(),
        ]);
    }
}
