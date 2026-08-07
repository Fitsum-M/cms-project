<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'username',
    'password',
    'bio',
    'status',
    'invitation_token',
    'invitation_sent_at',
    'invited_by',
    'activated_at',
    'suspended_at',
])]
#[Hidden(['password', 'remember_token', 'invitation_token'])]
class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'invitation_sent_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active && ! $this->trashed();
    }

    public function isPendingActivation(): bool
    {
        return $this->status === UserStatus::PendingActivation && ! $this->trashed();
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended && ! $this->trashed();
    }

    /**
     * Assign exactly one role (SRS 11.2 / 15.6 — no multi-role, no per-user overrides).
     */
    public function assignSingleRole(UserRole|string $role): void
    {
        $roleName = $role instanceof UserRole ? $role->value : $role;

        if (UserRole::tryFrom($roleName) === null) {
            throw new InvalidArgumentException("Unknown role [{$roleName}].");
        }

        $this->syncRoles([$roleName]);
        $this->syncPermissions([]);
    }

    public function primaryRole(): ?UserRole
    {
        $name = $this->getRoleNames()->first();

        return is_string($name) ? UserRole::tryFrom($name) : null;
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(128)
            ->height(128)
            ->nonQueued()
            ->performOnCollections('avatar');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: $this->getFirstMediaUrl('avatar') ?: null;
    }
}
