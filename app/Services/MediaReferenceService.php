<?php

namespace App\Services;

use App\Contracts\MediaReferenceProvider;
use App\Models\MediaAsset;
use App\Support\Media\MediaReference;
use Illuminate\Support\Collection;

class MediaReferenceService
{
    /**
     * @param  iterable<MediaReferenceProvider>  $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    /**
     * @return Collection<int, MediaReference>
     */
    public function references(MediaAsset $asset): Collection
    {
        $items = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->referencesFor($asset) as $reference) {
                $items[] = $reference;
            }
        }

        return collect($items);
    }

    public function isReferenced(MediaAsset $asset): bool
    {
        return $this->references($asset)->isNotEmpty();
    }

    public function clearAll(MediaAsset $asset): void
    {
        foreach ($this->providers as $provider) {
            $provider->clearReferences($asset);
        }
    }

    public function formatReferenceList(MediaAsset $asset): string
    {
        $refs = $this->references($asset);

        if ($refs->isEmpty()) {
            return 'Not referenced.';
        }

        return $refs
            ->map(fn (MediaReference $ref): string => "{$ref->label}: {$ref->detail}")
            ->implode("\n");
    }
}
