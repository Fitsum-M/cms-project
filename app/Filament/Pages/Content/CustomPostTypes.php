<?php

namespace App\Filament\Pages\Content;

use App\Enums\Permission;
use App\Filament\Pages\PlaceholderPage;
use App\Filament\Resources\PostTypes\PostTypeResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Navigation parent for Manage Types + registered CPT listings (SRS 10.1 / 12.4.1).
 */
class CustomPostTypes extends PlaceholderPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Custom Post Types';

    protected static ?string $navigationParentItem = 'Posts';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Custom Post Types';

    protected static ?string $slug = 'content/posts/custom-types';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can(Permission::CustomPostTypesManage->value)
            || $user->can(Permission::PostsViewAll->value)
            || $user->can(Permission::PostsViewOwn->value);
    }

    public function mount(): void
    {
        if (auth()->user()?->can(Permission::CustomPostTypesManage->value)) {
            $this->redirect(PostTypeResource::getUrl('index'));

            return;
        }

        $this->redirect(\App\Filament\Resources\Posts\PostResource::getUrl('index'));
    }
}
