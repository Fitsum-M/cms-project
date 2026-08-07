<?php

namespace Database\Seeders;

use App\Enums\SettingGroup;
use App\Services\SettingsStore;
use App\Support\Settings\SeoDefaultsSettings;
use Illuminate\Database\Seeder;

class SeoDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $store = app(SettingsStore::class);

        if ($store->all(SettingGroup::SeoDefaults) !== []) {
            return;
        }

        app(SeoDefaultsSettings::class)->save(SeoDefaultsSettings::defaults());
    }
}
