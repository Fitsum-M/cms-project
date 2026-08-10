<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendContentService;
use App\Support\Settings\GeneralSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(FrontendContentService $content, GeneralSettings $settings): View
    {
        return view('frontend.home', [
            'siteTitle' => $settings->siteTitle(),
            'tagline' => $settings->tagline(),
            'posts' => $content->paginatedPosts(),
            'navPages' => $content->navigationPages(),
        ]);
    }
}
