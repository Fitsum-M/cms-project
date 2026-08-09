<?php

namespace App\Listeners\Audit;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

class LogAuthenticationEvents
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $this->audit->loginSucceeded($user, [
            'guard' => $event->guard,
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        $email = is_string($event->credentials['email'] ?? null)
            ? $event->credentials['email']
            : null;

        $this->audit->loginFailed($user, $email, [
            'guard' => $event->guard,
        ]);
    }
}
