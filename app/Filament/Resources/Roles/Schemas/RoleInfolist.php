<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Scaffold stub — coverage % and permission grid land in Step 1.4.
 */
class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role')
                    ->schema([
                        TextEntry::make('name')->label('Role Name'),
                        TextEntry::make('guard_name')->label('Guard'),
                        TextEntry::make('users_count')
                            ->label('Users')
                            ->state(fn ($record): int => $record->users()->count()),
                        TextEntry::make('permissions_count')
                            ->label('Permissions')
                            ->state(fn ($record): int => $record->permissions()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}
