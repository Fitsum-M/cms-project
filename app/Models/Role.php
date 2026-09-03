<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Database-backed role (not a hard-coded enum). Admins manage roles from the UI;
 * seed data only creates the MVP defaults.
 */
class Role extends SpatieRole
{
    /** Protected system role name — cannot be renamed or deleted. */
    public const ADMINISTRATOR = 'Administrator';

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'color',
        'icon',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->name === self::ADMINISTRATOR;
    }

    public function displayColor(): string
    {
        return filled($this->color) ? $this->color : 'gray';
    }

    public function displayIcon(): string
    {
        return filled($this->icon) ? $this->icon : 'heroicon-o-shield-check';
    }

    public function displayDescription(): string
    {
        return filled($this->description) ? $this->description : 'Custom user-defined role.';
    }
}
