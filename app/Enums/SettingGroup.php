<?php

namespace App\Enums;

enum SettingGroup: string
{
    case General = 'general';
    case Reading = 'reading';
    case Permalinks = 'permalinks';
    case Media = 'media';
    case SeoDefaults = 'seo_defaults';
    case Email = 'email';
}
