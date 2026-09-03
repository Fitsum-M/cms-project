<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaDeletionService;
use App\Services\MediaUploadService;
use App\Support\Settings\SeoDefaultsSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SeoDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaReuseDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RoleSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        $this->seed(SeoDefaultsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_unreferenced_media_can_be_deleted(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('free.jpg', 100, 80),
            $admin,
        );

        app(MediaDeletionService::class)->delete($asset);

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
    }

    public function test_delete_is_blocked_when_media_is_referenced_and_lists_references(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('og.jpg', 120, 90),
            $admin,
        );

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::OG_IMAGE_ID => $asset->id,
        ]);

        $this->assertTrue($asset->isReferenced());

        try {
            app(MediaDeletionService::class)->delete($asset);
            $this->fail('Expected ValidationException for referenced media.');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->implode(' ');
            $this->assertStringContainsString('SEO Defaults', $message);
            $this->assertStringContainsString('Open Graph', $message);
        }

        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
    }

    public function test_administrator_force_delete_clears_references_and_removes_asset(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('force.jpg', 120, 90),
            $admin,
        );

        app(SeoDefaultsSettings::class)->save([
            ...app(SeoDefaultsSettings::class)->all(),
            SeoDefaultsSettings::OG_IMAGE_ID => $asset->id,
        ]);

        $this->assertSame($asset->id, app(SeoDefaultsSettings::class)->ogImageId());

        app(MediaDeletionService::class)->forceDelete($asset);

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertNull(app(SeoDefaultsSettings::class)->ogImageId());
    }

    public function test_editor_can_delete_unreferenced_but_cannot_force_delete(): void
    {
        $editor = $this->makeUser('Editor');
        $asset = MediaAsset::factory()->create(['uploaded_by' => $editor->id]);

        $this->assertTrue($editor->can('delete', $asset));
        $this->assertFalse($editor->can('forceDelete', $asset));

        Livewire::actingAs($editor)
            ->test(EditMediaAsset::class, ['record' => $asset->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('forceDelete');
    }

    public function test_force_delete_action_visible_only_to_administrator(): void
    {
        $admin = $this->makeUser('Administrator');
        $editor = $this->makeUser('Editor');
        $asset = MediaAsset::factory()->create(['uploaded_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(ListMediaAssets::class)
            ->assertSuccessful()
            ->assertTableActionVisible('forceDelete', $asset);

        Livewire::actingAs($editor)
            ->test(ListMediaAssets::class)
            ->assertSuccessful()
            ->assertTableActionHidden('forceDelete', $asset);
    }

    public function test_seo_defaults_og_image_options_use_media_asset_ids(): void
    {
        $admin = $this->makeUser('Administrator');
        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('banner.png', 200, 100),
            $admin,
        );

        $options = SeoDefaultsSettings::ogImageOptions();

        $this->assertArrayHasKey($asset->id, $options);
        $this->assertTrue(SeoDefaultsSettings::mediaImageExists($asset->id));
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
