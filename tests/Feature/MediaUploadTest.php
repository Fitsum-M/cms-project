<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\Dam\UploadMedia;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Support\Settings\MediaSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaUploadTest extends TestCase
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

    public function test_administrator_can_upload_single_file_via_service(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $file = UploadedFile::fake()->image('hero-shot.jpg', 640, 480);

        $asset = app(MediaUploadService::class)->upload($file, $admin);

        $this->assertSame('hero shot', $asset->title);
        $this->assertSame('hero-shot.jpg', $asset->original_file_name);
        $this->assertSame($admin->id, $asset->uploaded_by);
        $this->assertTrue($asset->isImage());
        $this->assertNotNull($asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION));
        $this->assertDatabaseCount('media_assets', 1);
        $this->assertDatabaseCount('media', 1);
    }

    public function test_bulk_upload_creates_multiple_assets(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $files = [
            UploadedFile::fake()->image('a.png'),
            UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ];

        $assets = app(MediaUploadService::class)->uploadMany($files, $admin);

        $this->assertCount(2, $assets);
        $this->assertDatabaseCount('media_assets', 2);
        $this->assertDatabaseCount('media', 2);
    }

    public function test_disallowed_extension_is_rejected(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        app(MediaSettings::class)->save([
            ...app(MediaSettings::class)->all(),
            MediaSettings::ALLOWED_FILE_TYPES => ['png'],
        ]);

        $this->expectException(ValidationException::class);

        app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('photo.jpg'),
            $admin,
        );
    }

    public function test_oversized_file_is_rejected(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        app(MediaSettings::class)->save([
            ...app(MediaSettings::class)->all(),
            MediaSettings::UPLOAD_MAX_FILE_SIZE_MB => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(MediaUploadService::class)->upload(
            UploadedFile::fake()->create('big.pdf', 2_048, 'application/pdf'),
            $admin,
        );
    }

    public function test_author_can_access_upload_page_contributor_cannot(): void
    {
        $author = $this->makeUser(UserRole::Author);
        $contributor = $this->makeUser(UserRole::Contributor);

        $this->assertTrue($author->can(Permission::MediaUpload->value));
        $this->assertFalse($contributor->can(Permission::MediaUpload->value));

        Livewire::actingAs($author)
            ->test(UploadMedia::class)
            ->assertSuccessful();

        Livewire::actingAs($contributor)
            ->test(UploadMedia::class)
            ->assertForbidden();
    }

    public function test_upload_page_persists_files_and_lists_them_in_library(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $file = UploadedFile::fake()->image('cover.webp', 320, 240);

        Livewire::actingAs($admin)
            ->test(UploadMedia::class)
            ->fillForm([
                'files' => [$file],
            ])
            ->call('submitUpload')
            ->assertHasNoFormErrors()
            ->assertRedirect(
                \App\Filament\Resources\MediaAssets\MediaAssetResource::getUrl('index', [
                    'filters' => [
                        'folder_scope' => [
                            'value' => 'unfiled',
                        ],
                    ],
                ]),
            );

        $this->assertDatabaseCount('media_assets', 1);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(MediaAsset::query()->get());
    }

    public function test_contributor_can_view_library_but_not_upload_action_target(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);

        MediaAsset::factory()->create([
            'uploaded_by' => $this->makeUser(UserRole::Administrator)->id,
            'title' => 'Shared image',
        ]);

        Livewire::actingAs($contributor)
            ->test(ListMediaAssets::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(MediaAsset::query()->get());
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
