<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin panel user profile updates (invite/suspend/delete stay on UserLifecycleService).
 */
class UserAdminService
{
    public function __construct(
        private readonly RoleAssignmentService $roles,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, username: string, email: string, bio?: ?string, role?: string, password?: string}  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $actor, User $target, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $validated = validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($target->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string'],
            'role' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (! Role::query()->where('name', $value)->where('guard_name', 'web')->exists()) {
                    $fail('The selected role is invalid.');
                }
            }],
        ])->validate();

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'bio' => $validated['bio'] ?? null,
        ];

        $fields = ['name', 'username', 'email', 'bio'];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
            $fields[] = 'password';
        }

        $target->forceFill($payload)->save();

        $this->audit->userEvent('updated', $target->fresh() ?? $target, $actor, [
            'fields' => $fields,
        ]);

        if (array_key_exists('role', $validated) && filled($validated['role'])) {
            $this->roles->assign($actor, $target, $validated['role']);
        }

        return $target->fresh() ?? $target;
    }
}
