<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\System\ReadingSettingsPage;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\ReadingSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReadingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_reading_settings(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(ReadingSettingsPage::class)
            ->fillForm([
                ReadingSettings::HOMEPAGE_PAGE_ID => null,
                ReadingSettings::POSTS_PAGE_ID => null,
                ReadingSettings::POSTS_PER_PAGE => 25,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(ReadingSettings::class);

        $this->assertNull($settings->homepagePageId());
        $this->assertNull($settings->postsPageId());
        $this->assertSame(25, $settings->postsPerPage());
    }

    public function test_non_administrator_cannot_access_reading_settings(): void
    {
        $editor = $this->makeUser(UserRole::Editor);

        $this->assertFalse($editor->can(Permission::SettingsView->value));

        Livewire::actingAs($editor)
            ->test(ReadingSettingsPage::class)
            ->assertForbidden();
    }

    public function test_posts_per_page_must_be_between_1_and_100(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(ReadingSettingsPage::class)
            ->fillForm([
                ReadingSettings::POSTS_PER_PAGE => 0,
            ])
            ->call('save')
            ->assertHasFormErrors([ReadingSettings::POSTS_PER_PAGE]);

        Livewire::actingAs($admin)
            ->test(ReadingSettingsPage::class)
            ->fillForm([
                ReadingSettings::POSTS_PER_PAGE => 101,
            ])
            ->call('save')
            ->assertHasFormErrors([ReadingSettings::POSTS_PER_PAGE]);
    }

    public function test_settings_store_persists_reading_group(): void
    {
        app(ReadingSettings::class)->save([
            ReadingSettings::HOMEPAGE_PAGE_ID => null,
            ReadingSettings::POSTS_PAGE_ID => null,
            ReadingSettings::POSTS_PER_PAGE => 15,
        ]);

        $stored = app(SettingsStore::class)->all(SettingGroup::Reading);

        $this->assertSame(15, $stored[ReadingSettings::POSTS_PER_PAGE]);
        $this->assertDatabaseHas('settings', [
            'group' => SettingGroup::Reading->value,
            'key' => ReadingSettings::POSTS_PER_PAGE,
            'value' => '15',
            'type' => 'integer',
        ]);
    }

    public function test_page_options_are_empty_until_pages_table_exists(): void
    {
        $this->assertFalse(ReadingSettings::pagesTableReady());
        $this->assertSame([], ReadingSettings::pageOptions());
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
