<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
    'avatar_url',
    'status',
    'invitation_token',
    'invitation_sent_at',
    'invited_by',
    'activated_at',
    'suspended_at',
])]
#[Hidden(['password', 'remember_token', 'invitation_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if (! $user->wasChanged('avatar_url')) {
                return;
            }

            $user->syncAvatarMediaFromProfileUpload();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
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

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: $this->getFirstMediaUrl('avatar')
            ?: (filled($this->attributes['avatar_url'] ?? null)
                ? Storage::disk('public')->url($this->attributes['avatar_url'])
                : null);
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
     * Role must already exist in the database.
     */
    public function assignSingleRole(string $role): void
    {
        if (! Role::query()->where('name', $role)->where('guard_name', 'web')->exists()) {
            throw new InvalidArgumentException("Unknown role [{$role}].");
        }

        $this->syncRoles([$role]);
        $this->syncPermissions([]);
    }

    public function primaryRole(): ?Role
    {
        $this->loadMissing('roles');

        $role = $this->roles->first();

        return $role instanceof Role ? $role : null;
    }

    public function primaryRoleName(): ?string
    {
        return $this->getRoleNames()->first();
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

    /**
     * Keep the Spatie `avatar` collection in sync with Breezy profile uploads (SRS 15.2).
     */
    public function syncAvatarMediaFromProfileUpload(): void
    {
        $path = $this->attributes['avatar_url'] ?? null;

        if (! is_string($path) || $path === '') {
            $this->clearMediaCollection('avatar');

            return;
        }

        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        $this->clearMediaCollection('avatar');

        $this->addMedia(Storage::disk('public')->path($path))
            ->preservingOriginal()
            ->toMediaCollection('avatar');
    }
}
