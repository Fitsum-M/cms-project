<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\SettingGroup;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\System\MediaSettingsPage;
use App\Models\User;
use App\Services\SettingsStore;
use App\Support\Settings\MediaSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrator_can_save_media_settings(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(MediaSettingsPage::class)
            ->fillForm([
                MediaSettings::THUMBNAIL_WIDTH => 200,
                MediaSettings::THUMBNAIL_HEIGHT => 200,
                MediaSettings::MEDIUM_WIDTH => 400,
                MediaSettings::MEDIUM_HEIGHT => 400,
                MediaSettings::LARGE_WIDTH => 1200,
                MediaSettings::LARGE_HEIGHT => 1200,
                MediaSettings::UPLOAD_MAX_FILE_SIZE_MB => 25,
                MediaSettings::DEFAULT_UPLOAD_FOLDER_ID => null,
                MediaSettings::ALLOWED_FILE_TYPES => ['jpg', 'png', 'pdf'],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(MediaSettings::class);

        $this->assertSame(200, $settings->thumbnailWidth());
        $this->assertSame(400, $settings->mediumWidth());
        $this->assertSame(1200, $settings->largeWidth());
        $this->assertSame(25, $settings->uploadMaxFileSizeMb());
        $this->assertSame(25 * 1024 * 1024, $settings->uploadMaxFileSizeBytes());
        $this->assertNull($settings->defaultUploadFolderId());
        $this->assertSame(['jpg', 'png', 'pdf'], $settings->allowedFileTypes());
        $this->assertTrue($settings->allowsExtension('PNG'));
        $this->assertFalse($settings->allowsExtension('zip'));
    }

    public function test_non_administrator_cannot_access_media_settings(): void
    {
        $editor = $this->makeUser(UserRole::Editor);

        $this->assertFalse($editor->can(Permission::SettingsView->value));

        Livewire::actingAs($editor)
            ->test(MediaSettingsPage::class)
            ->assertForbidden();
    }

    public function test_upload_max_must_be_at_least_one_mb(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(MediaSettingsPage::class)
            ->fillForm([
                MediaSettings::UPLOAD_MAX_FILE_SIZE_MB => 0,
            ])
            ->call('save')
            ->assertHasFormErrors([MediaSettings::UPLOAD_MAX_FILE_SIZE_MB]);
    }

    public function test_allowed_file_types_required(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        Livewire::actingAs($admin)
            ->test(MediaSettingsPage::class)
            ->fillForm([
                MediaSettings::ALLOWED_FILE_TYPES => [],
            ])
            ->call('save')
            ->assertHasFormErrors([MediaSettings::ALLOWED_FILE_TYPES]);
    }

    public function test_settings_store_persists_media_group(): void
    {
        app(MediaSettings::class)->save(MediaSettings::defaults());

        $stored = app(SettingsStore::class)->all(SettingGroup::Media);

        $this->assertSame(150, $stored[MediaSettings::THUMBNAIL_WIDTH]);
        $this->assertSame(10, $stored[MediaSettings::UPLOAD_MAX_FILE_SIZE_MB]);
        $this->assertContains('webp', $stored[MediaSettings::ALLOWED_FILE_TYPES]);
        $this->assertDatabaseHas('settings', [
            'group' => SettingGroup::Media->value,
            'key' => MediaSettings::UPLOAD_MAX_FILE_SIZE_MB,
            'value' => '10',
            'type' => 'integer',
        ]);
    }

    public function test_folder_options_include_created_folders(): void
    {
        $this->assertTrue(MediaSettings::foldersTableReady());

        $folder = \App\Models\Folder::factory()->create(['name' => 'Heroes']);

        $options = MediaSettings::folderOptions();

        $this->assertArrayHasKey($folder->id, $options);
        $this->assertSame('Heroes', $options[$folder->id]);
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
