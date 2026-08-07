<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use App\Support\Settings\MediaSettings;
use Illuminate\Database\Seeder;

class MediaSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = app(SettingsStore::class);

        if ($store->all(SettingGroup::Media) !== []) {
            return;
        }

        app(MediaSettings::class)->save(MediaSettings::defaults());
    }
}
