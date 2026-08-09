<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Support\PageTemplateRegistry;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * P.08 — Catalog of system-registered page templates (SRS 12.3.5).
 */
class PageTemplates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page Templates';

    protected static ?string $navigationParentItem = 'Pages';

    protected static ?int $navigationSort = 24;

    protected static ?string $title = 'Page Templates';

    protected static ?string $slug = 'content/pages/templates';

    protected string $view = 'filament.pages.content.page-templates';

    /**
     * @var list<array{key: string, label: string, description: string|null, icon: string}>
     */
    public array $templates = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::PagesViewOwn->value)
            || $user->can(Permission::PagesViewAll->value);
    }

    public function mount(): void
    {
        $this->templates = PageTemplateRegistry::catalog();
    }
}
