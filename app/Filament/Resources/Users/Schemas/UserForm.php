<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->description('Core identity fields (SRS 15.2).')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete(false),
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->autocomplete(false),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->autocomplete(false),
                        Textarea::make('bio')
                            ->label('Biography')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Role')
                    ->description('Exactly one role per user. Role changes take effect immediately (SRS 15.5).')
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->options(fn (): array => \Spatie\Permission\Models\Role::query()
                                ->where('guard_name', 'web')
                                ->pluck('name', 'name')
                                ->all())
                            ->required()
                            ->native(false)
                            ->helperText(fn (?string $state): ?string => filled($state)
                                ? (UserRole::tryFrom($state)?->description() ?? 'Custom user-defined role.')
                                : 'Assigned at creation; Administrators may change later.')
                            ->disabled(function (?User $record): bool {
                                $actor = auth()->user();

                                if ($actor === null) {
                                    return true;
                                }

                                if ($record === null) {
                                    return ! $actor->can(Permission::UsersCreate->value);
                                }

                                return ! $actor->can('updateRole', $record);
                            })
                            ->dehydrated(function (?User $record): bool {
                                $actor = auth()->user();

                                if ($actor === null) {
                                    return false;
                                }

                                if ($record === null) {
                                    return $actor->can(Permission::UsersCreate->value);
                                }

                                return $actor->can('updateRole', $record);
                            }),
                    ]),
            ]);
    }
}
