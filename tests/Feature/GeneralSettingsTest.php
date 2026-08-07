<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\System\GeneralSettingsPage;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\GeneralSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_general_settings(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(GeneralSettingsPage::class)
            ->fillForm([
                GeneralSettings::SITE_TITLE => 'Acme CMS',
                GeneralSettings::TAGLINE => 'Editorial first',
                GeneralSettings::TIMEZONE => 'America/Los_Angeles',
                GeneralSettings::DATE_FORMAT => 'Y-m-d',
                GeneralSettings::TIME_FORMAT => 'H:i',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(GeneralSettings::class);

        $this->assertSame('Acme CMS', $settings->siteTitle());
        $this->assertSame('Editorial first', $settings->tagline());
        $this->assertSame('America/Los_Angeles', $settings->timezone());
        $this->assertSame('Y-m-d', $settings->dateFormat());
        $this->assertSame('H:i', $settings->timeFormat());
        $this->assertSame('America/Los_Angeles', config('app.timezone'));
    }

    public function test_non_administrator_cannot_access_general_settings(): void
    {
        $editor = $this->makeUser(UserRole::Editor);

        $this->assertFalse($editor->can(Permission::SettingsView->value));

        Livewire::actingAs($editor)
            ->test(GeneralSettingsPage::class)
            ->assertForbidden();
    }

    public function test_settings_store_persists_group_key_value_type(): void
    {
        app(GeneralSettings::class)->save([
            GeneralSettings::SITE_TITLE => 'Stored Title',
            GeneralSettings::TAGLINE => 'Stored tagline',
            GeneralSettings::TIMEZONE => 'UTC',
            GeneralSettings::DATE_FORMAT => 'F j, Y',
            GeneralSettings::TIME_FORMAT => 'g:i a',
        ]);

        $stored = app(SettingsStore::class)->all(SettingGroup::General);

        $this->assertSame('Stored Title', $stored[GeneralSettings::SITE_TITLE]);
        $this->assertDatabaseHas('settings', [
            'group' => SettingGroup::General->value,
            'key' => GeneralSettings::SITE_TITLE,
            'value' => 'Stored Title',
            'type' => 'string',
        ]);
    }

    public function test_site_title_validation_max_length(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(GeneralSettingsPage::class)
            ->fillForm([
                GeneralSettings::SITE_TITLE => str_repeat('a', 256),
                GeneralSettings::TAGLINE => 'ok',
                GeneralSettings::TIMEZONE => 'UTC',
                GeneralSettings::DATE_FORMAT => 'Y-m-d',
                GeneralSettings::TIME_FORMAT => 'g:i a',
            ])
            ->call('save')
            ->assertHasFormErrors([GeneralSettings::SITE_TITLE]);
    }

    private function makeUser(UserRole $role): User
    {
        // Ensure permission rows exist even if RoleSeeder order differs in future.
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
