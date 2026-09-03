<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\Folder;
use App\Models\MediaAsset;
use App\Models\User;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaLibraryViewAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_search_is_scoped_to_current_folder_unless_search_all_folders_is_enabled(): void
    {
        $admin = $this->makeUser('Administrator');
        $folderA = Folder::factory()->create(['name' => 'Folder A']);
        $folderB = Folder::factory()->create(['name' => 'Folder B']);

        $inFolderA = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $folderA->id,
            'title' => 'SharedNeedle Alpha',
        ]);
        $inFolderB = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $folderB->id,
            'title' => 'SharedNeedle Beta',
        ]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('folder_scope', (string) $folderA->id)
            ->searchTable('SharedNeedle')
            ->assertCanSeeTableRecords([$inFolderA])
            ->assertCanNotSeeTableRecords([$inFolderB]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('folder_scope', (string) $folderA->id)
            ->filterTable('search_all_folders')
            ->searchTable('SharedNeedle')
            ->assertCanSeeTableRecords([$inFolderA, $inFolderB]);
    }

    public function test_search_all_folders_does_not_bypass_folder_when_not_searching(): void
    {
        $admin = $this->makeUser('Administrator');
        $folder = Folder::factory()->create(['name' => 'Scoped']);

        $inFolder = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $folder->id,
            'title' => 'In scoped folder',
        ]);
        $elsewhere = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => null,
            'title' => 'Elsewhere',
        ]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('folder_scope', (string) $folder->id)
            ->filterTable('search_all_folders')
            ->assertCanSeeTableRecords([$inFolder])
            ->assertCanNotSeeTableRecords([$elsewhere]);
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
