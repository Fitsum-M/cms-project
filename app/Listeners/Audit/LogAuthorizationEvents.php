<?php

namespace App\Listeners\Audit;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class LogAuthorizationEvents
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handleAuthorizationException(AuthorizationException $exception): void
    {
        $actor = Auth::user();

        $this->audit->permissionDenied(
            $actor instanceof User ? $actor : null,
            $this->resolveAbility($exception),
            [
                'message' => $exception->getMessage(),
            ],
        );
    }

    private function resolveAbility(AuthorizationException $exception): string
    {
        if (method_exists($exception, 'ability') && filled($exception->ability())) {
            return (string) $exception->ability();
        }

        $message = trim($exception->getMessage());

        return $message !== '' ? $message : 'authorization';
    }
}
