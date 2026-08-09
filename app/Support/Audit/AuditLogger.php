<?php

namespace App\Support\Audit;

use App\Contracts\HasContentLifecycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Structured audit / security logging (SRS 15.8 / 19.8).
 *
 * Never logs passwords, tokens, or other secrets.
 */
class AuditLogger
{
    public const SECURITY_CHANNEL = 'security';

    public const CONTENT_CHANNEL = 'audit';

    /**
     * @param  array<string, mixed>  $context
     */
    public function security(string $event, array $context = [], string $level = 'warning'): void
    {
        $this->write(self::SECURITY_CHANNEL, $level, $event, $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function content(string $event, array $context = []): void
    {
        $this->write(self::CONTENT_CHANNEL, 'info', $event, $this->sanitize($context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function loginSucceeded(User $user, array $context = []): void
    {
        $this->security('auth.login.succeeded', [
            'user_id' => $user->id,
            'email' => $user->email,
            ...$context,
        ], 'warning');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function loginFailed(?User $user, ?string $email, array $context = []): void
    {
        $this->security('auth.login.failed', [
            'user_id' => $user?->id,
            'email' => $email,
            ...$context,
        ], 'warning');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function permissionDenied(?User $actor, string $ability, array $context = []): void
    {
        $this->security('auth.permission.denied', [
            'actor_id' => $actor?->id,
            'ability' => $ability,
            ...$context,
        ], 'warning');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function userEvent(string $action, User $target, ?User $actor = null, array $context = []): void
    {
        $this->security('user.'.$action, [
            'actor_id' => ($actor ?? $this->actor())?->id,
            'target_user_id' => $target->id,
            'target_email' => $target->email,
            ...$context,
        ], 'warning');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function contentChanged(
        string $action,
        Model|HasContentLifecycle $content,
        ?User $actor = null,
        array $context = [],
    ): void {
        $this->content('content.'.$action, [
            'actor_id' => ($actor ?? $this->actor())?->id,
            'content_type' => $content instanceof Model ? $content::class : $content::class,
            'content_id' => $content->getKey(),
            'title' => $content instanceof HasContentLifecycle
                ? $content->contentTitle()
                : (string) ($content->getAttribute('title') ?? ''),
            'slug' => $content instanceof HasContentLifecycle
                ? $content->contentSlug()
                : (string) ($content->getAttribute('slug') ?? ''),
            'status' => $content instanceof HasContentLifecycle
                ? $content->contentStatus()->value
                : (string) ($content->getAttribute('status') ?? ''),
            ...$context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $blocked = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'plain_token',
            'invitation_token',
            'remember_token',
            'authorization',
            'cookie',
        ];

        $clean = [];

        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);

            foreach ($blocked as $needle) {
                if (str_contains($normalized, $needle)) {
                    continue 2;
                }
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);

                continue;
            }

            $clean[$key] = $value;
        }

        $clean['ip'] ??= request()->ip();
        $clean['url'] ??= request()->fullUrl();

        return $clean;
    }

    protected function actor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function write(string $channel, string $level, string $event, array $context): void
    {
        try {
            Log::channel($channel)->log($level, $event, $context);
        } catch (Throwable) {
            Log::log($level, $event, $context);
        }
    }
}
