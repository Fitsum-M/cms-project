<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->schema([
                        TextEntry::make('name')->label('Full Name'),
                        TextEntry::make('username')->label('Username'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('bio')
                            ->label('Biography')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Access')
                    ->schema([
                        TextEntry::make('role')
                            ->label('Role')
                            ->state(fn ($record): string => $record->primaryRoleName() ?? '—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?UserStatus $state): string => $state?->label() ?? '—')
                            ->color(fn (?UserStatus $state): string => match ($state) {
                                UserStatus::Active => 'success',
                                UserStatus::PendingActivation => 'warning',
                                UserStatus::Suspended => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('invitation_sent_at')
                            ->label('Invitation sent')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('activated_at')
                            ->label('Activated')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('suspended_at')
                            ->label('Suspended')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('invitedByUser.name')
                            ->label('Invited by')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
