<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\SlugConflictResolution;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\System\PermalinkSettingsPage;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\PermalinkSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermalinkSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_permalink_settings(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(PermalinkSettingsPage::class)
            ->fillForm([
                PermalinkSettings::POST_URL_STRUCTURE => '/{year}/{month}/{slug}/',
                PermalinkSettings::PAGE_URL_STRUCTURE => '/{slug}/',
                PermalinkSettings::AUTO_GENERATE_SLUGS => false,
                PermalinkSettings::CONFLICT_RESOLUTION => SlugConflictResolution::BlockSave->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(PermalinkSettings::class);

        $this->assertSame('/{year}/{month}/{slug}/', $settings->postUrlStructure());
        $this->assertSame('/{slug}/', $settings->pageUrlStructure());
        $this->assertFalse($settings->autoGenerateSlugs());
        $this->assertSame(SlugConflictResolution::BlockSave, $settings->conflictResolution());
    }

    public function test_non_administrator_cannot_access_permalink_settings(): void
    {
        $editor = $this->makeUser(UserRole::Editor);

        $this->assertFalse($editor->can(Permission::SettingsView->value));

        Livewire::actingAs($editor)
            ->test(PermalinkSettingsPage::class)
            ->assertForbidden();
    }

    public function test_settings_store_persists_permalinks_group(): void
    {
        app(PermalinkSettings::class)->save(PermalinkSettings::defaults());

        $stored = app(SettingsStore::class)->all(SettingGroup::Permalinks);

        $this->assertSame('/{post-type}/{slug}/', $stored[PermalinkSettings::POST_URL_STRUCTURE]);
        $this->assertTrue($stored[PermalinkSettings::AUTO_GENERATE_SLUGS]);
        $this->assertDatabaseHas('settings', [
            'group' => SettingGroup::Permalinks->value,
            'key' => PermalinkSettings::CONFLICT_RESOLUTION,
            'value' => SlugConflictResolution::AppendNumber->value,
            'type' => 'string',
        ]);
    }

    public function test_build_post_and_page_paths_from_structures(): void
    {
        app(PermalinkSettings::class)->save([
            PermalinkSettings::POST_URL_STRUCTURE => '/{post-type}/{slug}/',
            PermalinkSettings::PAGE_URL_STRUCTURE => '/{parent-slug}/{slug}/',
            PermalinkSettings::AUTO_GENERATE_SLUGS => true,
            PermalinkSettings::CONFLICT_RESOLUTION => SlugConflictResolution::AppendNumber->value,
        ]);

        $settings = app(PermalinkSettings::class);

        $this->assertSame(
            '/blog/hello-world/',
            $settings->buildPostPath(['slug' => 'hello-world', 'post_type' => 'blog']),
        );

        $this->assertSame(
            '/about/team/',
            $settings->buildPagePath(['slug' => 'team', 'parent_slug' => 'about']),
        );

        $this->assertSame(
            '/team/',
            $settings->buildPagePath(['slug' => 'team', 'parent_slug' => null]),
        );
    }

    public function test_structure_without_slug_token_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(PermalinkSettings::class)->save([
            PermalinkSettings::POST_URL_STRUCTURE => '/{post-type}/',
            PermalinkSettings::PAGE_URL_STRUCTURE => '/{slug}/',
            PermalinkSettings::AUTO_GENERATE_SLUGS => true,
            PermalinkSettings::CONFLICT_RESOLUTION => SlugConflictResolution::AppendNumber->value,
        ]);
    }

    private function makeUser(UserRole $role): User
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
