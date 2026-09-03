<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GeneralSettingsSeeder::class,
            ReadingSettingsSeeder::class,
            PermalinkSettingsSeeder::class,
            MediaSettingsSeeder::class,
            SeoDefaultsSeeder::class,
            EmailSettingsSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@cms.local'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'bio' => null,
                'status' => UserStatus::Active,
                'activated_at' => now(),
                'invitation_token' => null,
                'invitation_sent_at' => null,
                'suspended_at' => null,
            ],
        );

        $admin->assignSingleRole('Administrator');

        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
