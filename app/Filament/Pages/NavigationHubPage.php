<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Navigation hub: appears in the sidebar and immediately redirects to a real UI.
 *
 * Used for §10.1 parent items (Posts, Pages, CPT) that exist for nesting / labels,
 * not as standalone screens. IAM user hubs and TaxonomiesGroup were removed.
 */
abstract class NavigationHubPage extends Page
{
    protected string $view = 'filament.pages.navigation-hub';
}
