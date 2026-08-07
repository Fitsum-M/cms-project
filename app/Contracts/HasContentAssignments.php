<?php

namespace App\Contracts;

interface HasContentAssignments
{
    public function hasAssignedContent(): bool;

    public function assignedContentCount(): int;

    /**
     * Human label for error messages (e.g. "category", "tag", "term").
     */
    public function taxonomyLabel(): string;
}
