<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Folders\Pages\CreateFolder;
use App\Filament\Resources\Folders\Pages\ListFolders;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\Folder;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\FolderService;
use App\Support\Settings\MediaSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaFolderTest extends TestCase
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

    public function test_can_create_nested_folders_with_unique_names_per_parent(): void
    {
        $service = app(FolderService::class);

        $root = $service->create(['name' => 'Campaigns']);
        $child = $service->create(['name' => 'Spring', 'parent_id' => $root->id]);

        $this->assertSame($root->id, $child->parent_id);
        $this->assertDatabaseHas('folders', ['name' => 'Spring', 'parent_id' => $root->id]);

        // Same name allowed under a different parent
        $service->create(['name' => 'Spring', 'parent_id' => null]);

        $this->expectException(ValidationException::class);
        $service->create(['name' => 'Spring', 'parent_id' => $root->id]);
    }

    public function test_folder_drag_reparent_blocks_cycles(): void
    {
        $service = app(FolderService::class);
        $parent = $service->create(['name' => 'Parent']);
        $child = $service->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $service->move($child, null);
        $this->assertNull($child->fresh()->parent_id);

        $service->move($child, $parent->id);
        $this->assertSame($parent->id, $child->fresh()->parent_id);

        $this->expectException(ValidationException::class);
        $service->move($parent, $child->id);
    }

    public function test_media_can_be_moved_between_folders(): void
    {
        $admin = $this->makeUser('Administrator');
        $folderA = Folder::factory()->create(['name' => 'A']);
        $folderB = Folder::factory()->create(['name' => 'B']);

        $asset = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $folderA->id,
        ]);

        app(FolderService::class)->moveMedia([$asset->id], $folderB->id);

        $this->assertSame($folderB->id, $asset->fresh()->folder_id);

        app(FolderService::class)->moveMedia([$asset->id], null);
        $this->assertNull($asset->fresh()->folder_id);
    }

    public function test_library_filters_by_folder_and_bulk_move_works(): void
    {
        $admin = $this->makeUser('Administrator');
        $folder = Folder::factory()->create(['name' => 'Heroes']);
        $other = Folder::factory()->create(['name' => 'Other']);

        $inFolder = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $folder->id,
            'title' => 'In folder',
        ]);
        $unfiled = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => null,
            'title' => 'Unfiled item',
        ]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('folder_scope', (string) $folder->id)
            ->assertCanSeeTableRecords([$inFolder])
            ->assertCanNotSeeTableRecords([$unfiled]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('folder_scope', 'unfiled')
            ->assertCanSeeTableRecords([$unfiled])
            ->assertCanNotSeeTableRecords([$inFolder]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->callTableBulkAction('moveToFolder', [$unfiled], [
                'folder_id' => $other->id,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame($other->id, $unfiled->fresh()->folder_id);
    }

    public function test_folders_page_create_and_drag_move(): void
    {
        $admin = $this->makeUser('Administrator');
        $parent = Folder::factory()->create(['name' => 'Root']);
        $child = Folder::factory()->create(['name' => 'Nested', 'parent_id' => null]);

        Livewire::actingAs($admin)
            ->test(CreateFolder::class)
            ->fillForm([
                'name' => 'Created',
                'parent_id' => $parent->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('folders', [
            'name' => 'Created',
            'parent_id' => $parent->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFolders::class)
            ->call('moveFolder', $child->id, $parent->id)
            ->assertSuccessful();

        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_recursive_delete_unfiles_media_and_removes_children(): void
    {
        $admin = $this->makeUser('Administrator');
        $root = Folder::factory()->create(['name' => 'Root']);
        $child = Folder::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);
        $asset = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'folder_id' => $child->id,
        ]);

        try {
            app(FolderService::class)->delete($root, recursive: false);
            $this->fail('Expected ValidationException when deleting a non-empty folder without recursive flag.');
        } catch (ValidationException) {
            // expected
        }

        app(FolderService::class)->delete($root, recursive: true);

        $this->assertDatabaseMissing('folders', ['id' => $root->id]);
        $this->assertDatabaseMissing('folders', ['id' => $child->id]);
        $this->assertNull($asset->fresh()->folder_id);
    }

    public function test_media_settings_folder_options_include_hierarchy(): void
    {
        $this->assertTrue(MediaSettings::foldersTableReady());

        $root = Folder::factory()->create(['name' => 'Brand']);
        Folder::factory()->create(['name' => 'Logos', 'parent_id' => $root->id]);

        $options = MediaSettings::folderOptions();

        $this->assertContains('Brand', $options);
        $this->assertContains('Brand / Logos', $options);
    }

    public function test_contributor_can_view_folders_but_cannot_create(): void
    {
        $contributor = $this->makeUser('Contributor');

        Livewire::actingAs($contributor)
            ->test(ListFolders::class)
            ->assertSuccessful()
            ->assertActionHidden('create');
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
