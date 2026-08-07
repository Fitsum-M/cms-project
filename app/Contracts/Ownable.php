<?php

namespace App\Contracts;

/**
 * Marks a record that belongs to a user (author / uploader / owner).
 * Content models (posts, pages, media) implement this in later phases.
 */
interface Ownable
{
    /**
     * Owning user id (author_id, user_id, uploaded_by, etc.).
     */
    public function ownerKey(): ?int;
}
