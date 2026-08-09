<?php

namespace App\Contracts;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasSeoMetadata
{
    public function seo(): MorphOne;

    public function contentTitle(): string;

    public function contentExcerptForSeo(): string;

    public function contentPublicPath(): string;

    public function featuredImageIdForSeo(): ?int;

    public function seoRecord(): ?SeoMetadata;
}
