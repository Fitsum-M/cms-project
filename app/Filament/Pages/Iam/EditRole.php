<?php

namespace App\Filament\Pages\Iam;

use App\Enums\Permission;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use UnitEnum;

class EditRole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Identity & Access Management';

    protected static ?string $navigationLabel = 'Edit Role';

    protected static ?string $title = 'Edit Role';

    protected static ?string $slug = 'iam/roles/{record}/edit';

    protected string $view = 'filament.pages.iam.edit-role';

    public int $roleId;
    public string $roleName = '';
    public string $email = '';
    public string $password = '';
    public array $rolePermissions = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::UsersEditRole->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(int $record): void
    {
        $role = Role::findById($record, 'web');
        $this->roleId = $role->id;
        $this->roleName = $role->name;
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();

        // Load corresponding user
        $user = \App\Models\User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role->name);
        })->first();
        if ($user) {
            $this->email = $user->email;
        } else {
            // Default email for dynamic updating
            $this->email = strtolower(str_replace(' ', '_', $role->name)) . '@example.com';
        }
    }

    /**
     * @return array<string, list<Permission>>
     */
    public function getPermissionsGrouped(): array
    {
        $grouped = [];

        foreach (Permission::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersEditRole->value), 403);

        $role = Role::findById($this->roleId, 'web');
        $oldName = $role->name;

        $associatedUser = \App\Models\User::whereHas('roles', function ($query) use ($oldName) {
            $query->where('name', $oldName);
        })->first();
        $ignoreId = $associatedUser ? $associatedUser->id : null;

        $rules = [
            'roleName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', 'web'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'rolePermissions' => ['array'],
        ];

        $this->validate($rules, [
            'roleName.unique' => 'This role name is already taken.',
            'email.unique' => 'This email is already taken.',
        ]);

        // Do not allow renaming the Administrator role
        if ($role->name === UserRole::Administrator->value && $this->roleName !== UserRole::Administrator->value) {
            Notification::make()
                ->danger()
                ->title('Renaming Blocked')
                ->body('The Administrator role name is system-protected and cannot be renamed.')
                ->send();
            return;
        }

        $role->name = $this->roleName;
        $role->save();

        $role->syncPermissions($this->rolePermissions);

        // Update corresponding user
        if ($associatedUser) {
            $associatedUser->name = $this->roleName;
            $associatedUser->email = $this->email;
            if (filled($this->password)) {
                $associatedUser->password = \Illuminate\Support\Facades\Hash::make($this->password);
            }
            $associatedUser->save();

            // Re-assign role if the role name was renamed
            if ($oldName !== $this->roleName) {
                $associatedUser->assignSingleRole($this->roleName);
            }
        } else {
            // Create user if missing
            $username = strtolower(str_replace(' ', '_', $this->roleName));
            $originalUsername = $username;
            $counter = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $originalUsername . '_' . $counter;
                $counter++;
            }

            $user = \App\Models\User::create([
                'name' => $this->roleName,
                'username' => $username,
                'email' => $this->email,
                'password' => \Illuminate\Support\Facades\Hash::make(filled($this->password) ? $this->password : 'password'),
                'status' => \App\Enums\UserStatus::Active,
                'activated_at' => now(),
            ]);
            $user->assignSingleRole($this->roleName);
        }

        Notification::make()
            ->success()
            ->title('Role Saved')
            ->body("Role '{$role->name}' and its associated user account have been updated successfully.")
            ->send();

        $this->redirect(RolesAndPermissions::getUrl());
    }
}
