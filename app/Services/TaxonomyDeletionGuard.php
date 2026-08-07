<?php

namespace App\Services;

use App\Contracts\HasContentAssignments;
use App\Models\CustomTaxonomy;
use Illuminate\Validation\ValidationException;

/**
 * Blocks taxonomy term deletion while content is assigned (SRS 13.1.5 / T.04).
 */
class TaxonomyDeletionGuard
{
    public function assertDeletable(HasContentAssignments $term): void
    {
        if (! $term->hasAssignedContent()) {
            return;
        }

        $label = $term->taxonomyLabel();
        $count = $term->assignedContentCount();

        throw ValidationException::withMessages([
            $label => sprintf(
                'This %s is assigned to %d content item%s and cannot be deleted until the content is reassigned or the %s is empty.',
                $label,
                $count,
                $count === 1 ? '' : 's',
                $label,
            ),
        ]);
    }

    public function assertTaxonomyDeletable(CustomTaxonomy $taxonomy): void
    {
        $assignedTerms = $taxonomy->terms()
            ->get()
            ->filter(fn ($term) => $term->hasAssignedContent());

        if ($assignedTerms->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'taxonomy' => sprintf(
                'This custom taxonomy has %d term%s still assigned to content and cannot be deleted until those terms are empty.',
                $assignedTerms->count(),
                $assignedTerms->count() === 1 ? '' : 's',
            ),
        ]);
    }
}
