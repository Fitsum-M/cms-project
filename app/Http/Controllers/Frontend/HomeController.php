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
        $sections = $content->organizationWebsiteSections();

        return view('frontend.site-home', [
            'siteTitle' => $settings->siteTitle(),
            'tagline' => $settings->tagline(),
            'heroPage' => $sections['heroPage'],
            'aboutPage' => $sections['aboutPage'],
            'stats' => $sections['stats'],
            'services' => $sections['services'],
            'team' => $sections['team'],
            'joinSteps' => $sections['joinSteps'],
            'faqs' => $sections['faqs'],
            'memberSaccos' => $sections['memberSaccos'],
            'impactStories' => $sections['impactStories'],
            'resources' => $sections['resources'],
            'news' => $sections['news'],
            'contactPage' => $sections['contactPage'],
            'navPages' => $content->navigationPages(),
        ]);
    }
}
