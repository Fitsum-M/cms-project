<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use App\Support\Settings\PermalinkSettings;
use Illuminate\Database\Seeder;

class PermalinkSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = app(SettingsStore::class);

        if ($store->all(SettingGroup::Permalinks) !== []) {
            return;
        }

        app(PermalinkSettings::class)->save(PermalinkSettings::defaults());
    }
}
