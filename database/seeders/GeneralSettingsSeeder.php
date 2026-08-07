<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use App\Support\Settings\GeneralSettings;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = app(SettingsStore::class);

        if ($store->all(SettingGroup::General) !== []) {
            return;
        }

        app(GeneralSettings::class)->save(GeneralSettings::defaults());
    }
}
