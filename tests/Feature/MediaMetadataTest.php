<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
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

class MediaMetadataTest extends TestCase
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

    public function test_administrator_can_update_media_metadata(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Old title',
            'alt_text' => null,
            'caption' => null,
            'description' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(EditMediaAsset::class, ['record' => $asset->getKey()])
            ->fillForm([
                'title' => 'Hero banner',
                'alt_text' => 'Skyline at dusk',
                'caption' => 'City skyline',
                'description' => 'Used on the homepage hero.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $asset->refresh();

        $this->assertSame('Hero banner', $asset->title);
        $this->assertSame('Skyline at dusk', $asset->alt_text);
        $this->assertSame('City skyline', $asset->caption);
        $this->assertSame('Used on the homepage hero.', $asset->description);
    }

    public function test_author_can_edit_own_media_but_not_others(): void
    {
        $author = $this->makeUser(UserRole::Author);
        $other = $this->makeUser(UserRole::Administrator);

        $own = MediaAsset::factory()->create(['uploaded_by' => $author->id]);
        $foreign = MediaAsset::factory()->create(['uploaded_by' => $other->id]);

        Livewire::actingAs($author)
            ->test(EditMediaAsset::class, ['record' => $own->getKey()])
            ->fillForm([
                'title' => 'My asset',
                'alt_text' => 'Own alt',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('My asset', $own->fresh()->title);

        Livewire::actingAs($author)
            ->test(EditMediaAsset::class, ['record' => $foreign->getKey()])
            ->assertForbidden();
    }

    public function test_contributor_cannot_edit_media_metadata(): void
    {
        $contributor = $this->makeUser(UserRole::Contributor);
        $asset = MediaAsset::factory()->create([
            'uploaded_by' => $this->makeUser(UserRole::Administrator)->id,
        ]);

        Livewire::actingAs($contributor)
            ->test(EditMediaAsset::class, ['record' => $asset->getKey()])
            ->assertForbidden();
    }

    public function test_metadata_field_lengths_are_validated(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $asset = MediaAsset::factory()->create(['uploaded_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(EditMediaAsset::class, ['record' => $asset->getKey()])
            ->fillForm([
                'title' => str_repeat('t', 256),
                'alt_text' => str_repeat('a', 256),
                'caption' => str_repeat('c', 501),
                'description' => str_repeat('d', 2001),
            ])
            ->call('save')
            ->assertHasFormErrors([
                'title',
                'alt_text',
                'caption',
                'description',
            ]);
    }

    public function test_library_search_matches_title_filename_alt_caption_and_description(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);

        $byTitle = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'UniqueTitleNeedle',
            'original_file_name' => 'a.jpg',
        ]);
        $byFile = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Other',
            'original_file_name' => 'UniqueFileNeedle.png',
        ]);
        $byAlt = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Other',
            'original_file_name' => 'b.jpg',
            'alt_text' => 'UniqueAltNeedle',
        ]);
        $byCaption = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Other',
            'original_file_name' => 'c.jpg',
            'caption' => 'UniqueCaptionNeedle',
        ]);
        $byDescription = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Other',
            'original_file_name' => 'd.jpg',
            'description' => 'UniqueDescriptionNeedle',
        ]);
        $unrelated = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'No match',
            'original_file_name' => 'plain.jpg',
        ]);

        foreach ([
            'UniqueTitleNeedle' => $byTitle,
            'UniqueFileNeedle' => $byFile,
            'UniqueAltNeedle' => $byAlt,
            'UniqueCaptionNeedle' => $byCaption,
            'UniqueDescriptionNeedle' => $byDescription,
        ] as $needle => $match) {
            Livewire::actingAs($admin)
                ->test(ListMediaAssets::class)
                ->searchTable($needle)
                ->assertCanSeeTableRecords([$match])
                ->assertCanNotSeeTableRecords([$unrelated]);
        }
    }

    public function test_library_filters_by_file_type_uploader_and_upload_date(): void
    {
        $admin = $this->makeUser(UserRole::Administrator);
        $author = $this->makeUser(UserRole::Author);

        $image = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Photo',
            'mime_type' => 'image/jpeg',
            'original_file_name' => 'photo.jpg',
            'created_at' => now()->subDays(2),
        ]);
        $document = MediaAsset::factory()->create([
            'uploaded_by' => $author->id,
            'title' => 'Brief',
            'mime_type' => 'application/pdf',
            'original_file_name' => 'brief.pdf',
            'width' => null,
            'height' => null,
            'created_at' => now()->subDays(10),
        ]);
        $archive = MediaAsset::factory()->create([
            'uploaded_by' => $admin->id,
            'title' => 'Bundle',
            'mime_type' => 'application/zip',
            'original_file_name' => 'bundle.zip',
            'width' => null,
            'height' => null,
            'created_at' => now()->subDay(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('file_type', 'image')
            ->assertCanSeeTableRecords([$image])
            ->assertCanNotSeeTableRecords([$document, $archive]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('file_type', 'document')
            ->assertCanSeeTableRecords([$document])
            ->assertCanNotSeeTableRecords([$image, $archive]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('file_type', 'archive')
            ->assertCanSeeTableRecords([$archive])
            ->assertCanNotSeeTableRecords([$image, $document]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('uploaded_by', (string) $author->id)
            ->assertCanSeeTableRecords([$document])
            ->assertCanNotSeeTableRecords([$image, $archive]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->filterTable('uploaded_at', [
                'uploaded_from' => now()->subDays(3)->toDateString(),
                'uploaded_until' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$image, $archive])
            ->assertCanNotSeeTableRecords([$document]);
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
