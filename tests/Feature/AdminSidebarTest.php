<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    public function test_admin_sidebar_is_collapsible_on_desktop_with_reviewer_width(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertSame('16rem', $panel->getSidebarWidth());
        $this->assertFalse($panel->isSidebarFullyCollapsibleOnDesktop());
    }
}
