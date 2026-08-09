<?php

namespace App\Contracts;

use App\Models\MediaAsset;
use App\Support\Media\MediaReference;

interface MediaReferenceProvider
{
    /**
     * @return list<MediaReference>
     */
    public function referencesFor(MediaAsset $asset): array;

    /**
     * Clear references so force-delete can proceed (empty placeholders).
     */
    public function clearReferences(MediaAsset $asset): void;
}
