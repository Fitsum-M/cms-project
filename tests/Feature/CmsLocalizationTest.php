<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Localization readiness for custom CMS strings (SRS §19.9, GAP.NFR.02).
 */
class CmsLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_translation_file_loads(): void
    {
        $this->assertFileExists(lang_path('en/cms.php'));
    }

    public function test_navigation_group_strings_resolve(): void
    {
        $this->assertSame('Content', __('cms.navigation.groups.content'));
        $this->assertSame('Digital Asset Management', __('cms.navigation.groups.dam'));
        $this->assertSame('Identity & Access Management', __('cms.navigation.groups.iam'));
        $this->assertSame('System Configuration', __('cms.navigation.groups.system'));
    }

    public function test_dashboard_widget_strings_resolve_with_placeholders(): void
    {
        $this->assertSame('Overview', __('cms.dashboard.overview.heading'));
        $this->assertSame(
            'Last 10 edited posts and pages.',
            __('cms.dashboard.recent_content.description', ['count' => 10]),
        );
        $this->assertSame('Quick Actions', __('cms.dashboard.quick_actions.heading'));
    }

    public function test_table_label_strings_resolve(): void
    {
        $this->assertSame('Title', __('cms.tables.title'));
    }
}
