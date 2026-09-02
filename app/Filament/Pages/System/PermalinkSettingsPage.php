<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use Filament\Pages\Page;

class PermalinkSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'system/permalinks';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::SettingsView->value) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->redirect(SettingsPage::getUrl().'?tab=permalinks');
    }
}
