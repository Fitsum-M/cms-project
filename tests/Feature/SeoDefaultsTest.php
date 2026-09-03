<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\UserStatus;
use App\Filament\Pages\System\SettingsPage;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\GeneralSettings;
use App\Support\Settings\SeoDefaultsSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SeoDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_seo_defaults(): void
    {
        $admin = $this->makeUser('Administrator');

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                SeoDefaultsSettings::META_TITLE_PATTERN => '{title} — {site_title}',
                SeoDefaultsSettings::META_DESCRIPTION => 'Default site description',
                SeoDefaultsSettings::OG_IMAGE_ID => null,
                SeoDefaultsSettings::SCHEMA_TYPE => 'Article',
                SeoDefaultsSettings::ROBOTS => ['index', 'follow', 'noarchive'],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(SeoDefaultsSettings::class);

        $this->assertSame('{title} — {site_title}', $settings->metaTitlePattern());
        $this->assertSame('Default site description', $settings->metaDescription());
        $this->assertNull($settings->ogImageId());
        $this->assertSame('Article', $settings->schemaType());
        $this->assertSame(['index', 'follow', 'noarchive'], $settings->robots());
        $this->assertSame('index, follow, noarchive', $settings->robotsDirective());
    }

    public function test_editor_can_view_but_not_edit_seo_defaults(): void
    {
        $editor = $this->makeUser('Editor');

        $this->assertTrue($editor->can(Permission::SeoDefaultsView->value));
        $this->assertFalse($editor->can(Permission::SeoDefaultsEdit->value));
        $this->assertFalse($editor->can(Permission::SettingsView->value));

        Livewire::actingAs($editor)
            ->test(SettingsPage::class)
            ->assertOk()
            ->assertFormFieldExists(SeoDefaultsSettings::META_TITLE_PATTERN)
            ->assertFormFieldDoesNotExist(GeneralSettings::SITE_TITLE)
            ->fillForm([
                SeoDefaultsSettings::META_TITLE_PATTERN => 'Should not persist',
                SeoDefaultsSettings::SCHEMA_TYPE => 'NewsArticle',
                SeoDefaultsSettings::ROBOTS => ['noindex'],
            ])
            ->call('save')
            ->assertNotNotified();

        $settings = app(SeoDefaultsSettings::class);

        $this->assertSame(SeoDefaultsSettings::defaults()[SeoDefaultsSettings::META_TITLE_PATTERN], $settings->metaTitlePattern());
        $this->assertSame('WebPage', $settings->schemaType());
    }

    public function test_author_cannot_access_seo_defaults(): void
    {
        $author = $this->makeUser('Author');

        $this->assertFalse($author->can(Permission::SeoDefaultsView->value));

        Livewire::actingAs($author)
            ->test(SettingsPage::class)
            ->assertForbidden();
    }

    public function test_resolve_meta_title_uses_tokens(): void
    {
        app(GeneralSettings::class)->save([
            ...GeneralSettings::defaults(),
            GeneralSettings::SITE_TITLE => 'Acme',
        ]);

        app(SeoDefaultsSettings::class)->save([
            ...SeoDefaultsSettings::defaults(),
            SeoDefaultsSettings::META_TITLE_PATTERN => '{title} | {site_title}',
        ]);

        $resolved = app(SeoDefaultsSettings::class)->resolveMetaTitle(['title' => 'Hello']);

        $this->assertSame('Hello | Acme', $resolved);
    }

    public function test_custom_schema_type_is_persisted(): void
    {
        $admin = $this->makeUser('Administrator');

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->fillForm([
                SeoDefaultsSettings::META_TITLE_PATTERN => '{title} | {site_title}',
                SeoDefaultsSettings::META_DESCRIPTION => '',
                SeoDefaultsSettings::SCHEMA_TYPE => 'Custom',
                'custom_schema_type' => 'Product',
                SeoDefaultsSettings::ROBOTS => ['index', 'follow'],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Product', app(SeoDefaultsSettings::class)->schemaType());
    }

    public function test_settings_store_persists_seo_defaults_group(): void
    {
        app(SeoDefaultsSettings::class)->save(SeoDefaultsSettings::defaults());

        $stored = app(SettingsStore::class)->all(SettingGroup::SeoDefaults);

        $this->assertSame('{title} | {site_title}', $stored[SeoDefaultsSettings::META_TITLE_PATTERN]);
        $this->assertSame(['index', 'follow'], $stored[SeoDefaultsSettings::ROBOTS]);
        $this->assertDatabaseHas('settings', [
            'group' => SettingGroup::SeoDefaults->value,
            'key' => SeoDefaultsSettings::SCHEMA_TYPE,
            'value' => 'WebPage',
            'type' => 'string',
        ]);
    }

    private function makeUser(string $role): User
    {
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'activated_at' => now(),
        ]);

        $user->assignSingleRole($role);

        return $user->fresh();
    }
}
