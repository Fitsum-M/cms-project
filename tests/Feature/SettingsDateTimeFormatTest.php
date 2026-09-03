<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Widgets\RecentContentWidget;
use App\Models\User;
use App\Services\PostService;
use App\Support\Settings\GeneralSettings;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\MediaSettingsSeeder;
use Database\Seeders\PermalinkSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SettingsDateTimeFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GeneralSettingsSeeder::class);
        $this->seed(PermalinkSettingsSeeder::class);
        $this->seed(MediaSettingsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_general_settings_formatters_respect_configured_date_time_and_timezone(): void
    {
        app(GeneralSettings::class)->save([
            GeneralSettings::TIMEZONE => 'America/Los_Angeles',
            GeneralSettings::DATE_FORMAT => 'Y-m-d',
            GeneralSettings::TIME_FORMAT => 'H:i',
        ]);
        app(GeneralSettings::class)->applyRuntimeConfiguration();

        $settings = app(GeneralSettings::class);
        $value = Carbon::parse('2026-08-09 18:30:00', 'UTC');

        $this->assertSame('Y-m-d H:i', $settings->dateTimeFormat());
        $this->assertSame('2026-08-09', $settings->formatDate($value));
        $this->assertSame('11:30', $settings->formatTime($value));
        $this->assertSame('2026-08-09 11:30', $settings->formatDateTime($value));
    }

    public function test_posts_table_and_infolist_use_general_settings_datetime_format(): void
    {
        $admin = $this->makeUser('Administrator');

        app(GeneralSettings::class)->save([
            GeneralSettings::TIMEZONE => 'UTC',
            GeneralSettings::DATE_FORMAT => 'd/m/Y',
            GeneralSettings::TIME_FORMAT => 'H:i',
        ]);
        app(GeneralSettings::class)->applyRuntimeConfiguration();

        $publishedAt = Carbon::parse('2026-03-15 14:45:00', 'UTC');

        $post = app(PostService::class)->create([
            'title' => 'Formatted Timestamp Post',
            'status' => ContentStatus::Published->value,
            'published_at' => $publishedAt,
        ], $admin);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$post])
            ->assertSee('15/03/2026 14:45');

        Livewire::actingAs($admin)
            ->test(ViewPost::class, ['record' => $post->getKey()])
            ->assertSee('15/03/2026 14:45');
    }

    public function test_dashboard_recent_content_uses_general_settings_datetime_format(): void
    {
        $admin = $this->makeUser('Administrator');
        $this->actingAs($admin);

        app(GeneralSettings::class)->save([
            GeneralSettings::TIMEZONE => 'UTC',
            GeneralSettings::DATE_FORMAT => 'Y-m-d',
            GeneralSettings::TIME_FORMAT => 'g:i a',
        ]);
        app(GeneralSettings::class)->applyRuntimeConfiguration();

        Carbon::setTestNow('2026-08-09 16:05:00');

        app(PostService::class)->create([
            'title' => 'Dashboard Format Post',
            'status' => ContentStatus::Draft->value,
        ], $admin);

        Livewire::test(RecentContentWidget::class)
            ->assertSee('Dashboard Format Post')
            ->assertSee('2026-08-09 4:05 pm');

        Carbon::setTestNow();
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
