<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Support\Settings\MediaSettings;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaImageHandlingTest extends TestCase
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

    public function test_image_upload_generates_thumbnail_medium_and_large_conversions(): void
    {
        $admin = $this->makeUser('Administrator');
        $settings = app(MediaSettings::class);

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('landscape.jpg', 800, 600),
            $admin,
        );

        $media = $asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION);

        $this->assertNotNull($media);
        $this->assertFileExists($media->getPath());
        $this->assertSame(800, $asset->width);
        $this->assertSame(600, $asset->height);
        $this->assertGreaterThan(0, $asset->size);

        foreach (MediaAsset::imageConversions() as $conversion) {
            $this->assertTrue($asset->hasGeneratedConversion($conversion), "Missing conversion: {$conversion}");
            $this->assertFileExists($media->getPath($conversion));
        }

        [$thumbW, $thumbH] = getimagesize($media->getPath(MediaAsset::CONVERSION_THUMBNAIL));
        $this->assertLessThanOrEqual($settings->thumbnailWidth(), $thumbW);
        $this->assertLessThanOrEqual($settings->thumbnailHeight(), $thumbH);

        [$mediumW, $mediumH] = getimagesize($media->getPath(MediaAsset::CONVERSION_MEDIUM));
        $this->assertLessThanOrEqual($settings->mediumWidth(), $mediumW);
        $this->assertLessThanOrEqual($settings->mediumHeight(), $mediumH);

        [$largeW, $largeH] = getimagesize($media->getPath(MediaAsset::CONVERSION_LARGE));
        $this->assertLessThanOrEqual($settings->largeWidth(), $largeW);
        $this->assertLessThanOrEqual($settings->largeHeight(), $largeH);
    }

    public function test_original_file_is_preserved_alongside_conversions(): void
    {
        $admin = $this->makeUser('Administrator');

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('keep-me.png', 640, 480),
            $admin,
        );

        $media = $asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION);

        $this->assertNotNull($media);

        $originalPath = $media->getPath();
        $this->assertFileExists($originalPath);

        [$originalW, $originalH] = getimagesize($originalPath);
        $this->assertSame(640, $originalW);
        $this->assertSame(480, $originalH);

        $this->assertNotSame($originalPath, $media->getPath(MediaAsset::CONVERSION_THUMBNAIL));
        $this->assertFileExists($media->getPath(MediaAsset::CONVERSION_THUMBNAIL));
    }

    public function test_conversion_dimensions_follow_media_settings(): void
    {
        $admin = $this->makeUser('Administrator');
        $settings = app(MediaSettings::class);
        $settings->save([
            ...$settings->all(),
            MediaSettings::THUMBNAIL_WIDTH => 100,
            MediaSettings::THUMBNAIL_HEIGHT => 100,
            MediaSettings::MEDIUM_WIDTH => 200,
            MediaSettings::MEDIUM_HEIGHT => 200,
            MediaSettings::LARGE_WIDTH => 400,
            MediaSettings::LARGE_HEIGHT => 400,
        ]);

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('sized.jpg', 900, 700),
            $admin,
        );

        $media = $asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION);
        $this->assertNotNull($media);

        [$thumbW, $thumbH] = getimagesize($media->getPath(MediaAsset::CONVERSION_THUMBNAIL));
        $this->assertLessThanOrEqual(100, $thumbW);
        $this->assertLessThanOrEqual(100, $thumbH);

        [$mediumW] = getimagesize($media->getPath(MediaAsset::CONVERSION_MEDIUM));
        $this->assertLessThanOrEqual(200, $mediumW);

        [$largeW] = getimagesize($media->getPath(MediaAsset::CONVERSION_LARGE));
        $this->assertLessThanOrEqual(400, $largeW);
    }

    public function test_non_image_upload_does_not_generate_conversions(): void
    {
        $admin = $this->makeUser('Administrator');

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->create('brief.txt', 12, 'text/plain'),
            $admin,
        );

        foreach (MediaAsset::imageConversions() as $conversion) {
            $this->assertFalse($asset->hasGeneratedConversion($conversion));
        }

        $this->assertNull($asset->width);
        $this->assertNull($asset->height);
        $this->assertNotNull($asset->originalUrl());
    }

    public function test_image_conversions_are_dispatched_asynchronously(): void
    {
        Queue::fake();

        $admin = $this->makeUser('Administrator');

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('queued.jpg', 320, 240),
            $admin,
        );

        Queue::assertPushed(PerformConversionsJob::class);

        foreach (MediaAsset::imageConversions() as $conversion) {
            $this->assertFalse($asset->hasGeneratedConversion($conversion));
        }

        $this->assertNotNull($asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION));
        $this->assertFileExists($asset->getFirstMedia(MediaAsset::LIBRARY_COLLECTION)->getPath());
    }

    public function test_preview_url_prefers_thumbnail_conversion(): void
    {
        $admin = $this->makeUser('Administrator');

        $asset = app(MediaUploadService::class)->upload(
            UploadedFile::fake()->image('preview.jpg', 400, 300),
            $admin,
        );

        $this->assertNotNull($asset->conversionUrl(MediaAsset::CONVERSION_THUMBNAIL));
        $this->assertSame(
            $asset->conversionUrl(MediaAsset::CONVERSION_THUMBNAIL),
            $asset->previewUrl(),
        );
        $this->assertNotSame($asset->originalUrl(), $asset->previewUrl());
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
