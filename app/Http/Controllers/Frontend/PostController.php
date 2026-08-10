<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendContentService;
use App\Support\Settings\GeneralSettings;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(string $slug, FrontendContentService $content, GeneralSettings $settings): View
    {
        $post = $content->findPublicPost($slug);

        abort_if($post === null, 404);

        return view('frontend.post', [
            'siteTitle' => $settings->siteTitle(),
            'tagline' => $settings->tagline(),
            'post' => $post,
            'navPages' => $content->navigationPages(),
        ]);
    }
}
