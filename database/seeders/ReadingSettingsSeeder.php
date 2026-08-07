<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use App\Support\Settings\ReadingSettings;
use Illuminate\Database\Seeder;

class ReadingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = app(SettingsStore::class);

        if ($store->all(SettingGroup::Reading) !== []) {
            return;
        }

        app(ReadingSettings::class)->save(ReadingSettings::defaults());
    }
}
