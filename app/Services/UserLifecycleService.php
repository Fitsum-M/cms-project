<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserLifecycleService
{
    public const INVITATION_TTL_DAYS = 7;

    /**
     * Invitation stage: create a pending user with a role and send the activation link.
     *
     * @throws AuthorizationException
     */
    public function invite(
        array $attributes,
        UserRole $role,
        ?User $invitedBy = null,
        bool $sendNotification = true,
    ): User {
        if ($invitedBy !== null) {
            Gate::forUser($invitedBy)->authorize('create', User::class);
        }

        $plainToken = $this->generateInvitationToken();

        $user = User::query()->create([
            'name' => $attributes['name'],
            'username' => $attributes['username'],
            'email' => $attributes['email'],
            'bio' => $attributes['bio'] ?? null,
            'password' => Hash::make(Str::password(32)),
            'status' => UserStatus::PendingActivation,
            'invitation_token' => hash('sha256', $plainToken),
            'invitation_sent_at' => now(),
            'invited_by' => $invitedBy?->id,
            'activated_at' => null,
            'suspended_at' => null,
        ]);

        $user->assignSingleRole($role);

        if ($sendNotification) {
            $user->notify(new UserInvitationNotification($plainToken));
        }

        return $user;
    }

    /**
     * Resend invitation email with a fresh token (pending users only).
     */
    public function resendInvitation(User $user): string
    {
        if ($user->status !== UserStatus::PendingActivation) {
            throw new InvalidArgumentException('Only pending users can receive a new invitation.');
        }

        $plainToken = $this->generateInvitationToken();

        $user->forceFill([
            'invitation_token' => hash('sha256', $plainToken),
            'invitation_sent_at' => now(),
        ])->save();

        $user->notify(new UserInvitationNotification($plainToken));

        return $plainToken;
    }

    /**
     * Activation stage: set password and move to Active.
     */
    public function activate(User $user, string $plainToken, string $password): User
    {
        if ($user->status !== UserStatus::PendingActivation) {
            throw new InvalidArgumentException('This account is not awaiting activation.');
        }

        if ($user->trashed()) {
            throw new InvalidArgumentException('This account has been deleted.');
        }

        if (! $this->invitationTokenIsValid($user, $plainToken)) {
            throw new InvalidArgumentException('This invitation link is invalid or has expired.');
        }

        $user->forceFill([
            'password' => $password,
            'status' => UserStatus::Active,
            'invitation_token' => null,
            'invitation_sent_at' => null,
            'activated_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user->refresh();
    }

    public function suspend(User $user): User
    {
        if ($user->trashed()) {
            throw new InvalidArgumentException('Deleted users cannot be suspended.');
        }

        if ($user->status === UserStatus::Suspended) {
            return $user;
        }

        $user->forceFill([
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ])->save();

        return $user->refresh();
    }

    /**
     * @throws AuthorizationException
     */
    public function suspendAs(User $actor, User $target): User
    {
        Gate::forUser($actor)->authorize('suspend', $target);

        return $this->suspend($target);
    }

    public function reactivate(User $user): User
    {
        if ($user->trashed()) {
            throw new InvalidArgumentException('Restore the user before reactivating.');
        }

        if ($user->status === UserStatus::PendingActivation) {
            throw new InvalidArgumentException('Pending users must activate via invitation link.');
        }

        $user->forceFill([
            'status' => UserStatus::Active,
            'suspended_at' => null,
        ])->save();

        return $user->refresh();
    }

    /**
     * Soft-delete while preserving attribution (SRS 15.1 / 15.7).
     */
    public function softDelete(User $user): void
    {
        $user->forceFill([
            'invitation_token' => null,
        ])->save();

        $user->delete();
    }

    /**
     * @throws AuthorizationException
     */
    public function softDeleteAs(User $actor, User $target): void
    {
        Gate::forUser($actor)->authorize('delete', $target);

        $this->softDelete($target);
    }

    public function restore(User $user): User
    {
        if (! $user->trashed()) {
            return $user;
        }

        $user->restore();

        if ($user->status === UserStatus::Active || $user->activated_at !== null) {
            $user->forceFill([
                'status' => UserStatus::Suspended,
                'suspended_at' => now(),
            ])->save();
        }

        return $user->refresh();
    }

    public function findPendingByInvitationToken(string $plainToken): ?User
    {
        $hashed = hash('sha256', $plainToken);

        /** @var User|null $user */
        $user = User::query()
            ->where('invitation_token', $hashed)
            ->where('status', UserStatus::PendingActivation)
            ->first();

        if ($user === null || ! $this->invitationTokenIsValid($user, $plainToken)) {
            return null;
        }

        return $user;
    }

    public function invitationTokenIsValid(User $user, string $plainToken): bool
    {
        if ($user->invitation_token === null || $user->invitation_sent_at === null) {
            return false;
        }

        if (! hash_equals($user->invitation_token, hash('sha256', $plainToken))) {
            return false;
        }

        return $user->invitation_sent_at->addDays(self::INVITATION_TTL_DAYS)->isFuture();
    }

    private function generateInvitationToken(): string
    {
        return Str::random(64);
    }
}
