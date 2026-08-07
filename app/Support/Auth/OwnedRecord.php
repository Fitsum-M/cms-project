<?php

namespace App\Support\Auth;

use App\Contracts\Ownable;

/**
 * Lightweight ownable value object for authorization checks before
 * domain models (Post, Page, Media) exist.
 *
 * @phpstan-immutable
 */
final class OwnedRecord implements Ownable
{
    public function __construct(private readonly ?int $ownerId) {}

    public static function of(?int $ownerId): self
    {
        return new self($ownerId);
    }

    public function ownerKey(): ?int
    {
        return $this->ownerId;
    }
}
