<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Navigation hub: appears in the sidebar and immediately redirects to a real UI.
 *
 * No Content/IAM hub pages remain (moved to *Navigation helpers + Resources).
 * Kept as a shared base if a future hub is needed.
 */
abstract class NavigationHubPage extends Page
{
    protected string $view = 'filament.pages.navigation-hub';
}
