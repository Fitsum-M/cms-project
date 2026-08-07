<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Enums\SlugConflictResolution;
use App\Support\Settings\PermalinkSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class PermalinkSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Permalinks';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Permalink Settings';

    protected static ?string $slug = 'system/permalinks';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::SettingsView->value) ?? false;
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data')
            ->disabled(fn (): bool => ! $this->canEdit());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('URL structures')
                    ->description('Patterns used when generating public URLs for posts and pages. Every pattern must include {slug}.')
                    ->schema([
                        Select::make(PermalinkSettings::POST_URL_STRUCTURE)
                            ->label('URL Structure')
                            ->options(PermalinkSettings::postUrlStructureOptions())
                            ->required()
                            ->helperText('Default: /{post-type}/{slug}/'),
                        Select::make(PermalinkSettings::PAGE_URL_STRUCTURE)
                            ->label('Page URL Structure')
                            ->options(PermalinkSettings::pageUrlStructureOptions())
                            ->required()
                            ->helperText('Controls whether child pages nest under the parent slug.'),
                    ])
                    ->columns(1),
                Section::make('Slug behavior')
                    ->schema([
                        Toggle::make(PermalinkSettings::AUTO_GENERATE_SLUGS)
                            ->label('Slug Generation')
                            ->helperText('Auto-generate slugs from the title on save.')
                            ->required(),
                        Select::make(PermalinkSettings::CONFLICT_RESOLUTION)
                            ->label('Conflict Resolution')
                            ->options(SlugConflictResolution::options())
                            ->required()
                            ->rule(Rule::enum(SlugConflictResolution::class))
                            ->helperText('What happens when a generated or edited slug already exists.'),
                    ])
                    ->columns(1),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->sticky($this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->visible(fn (): bool => $this->canEdit()),
        ];
    }

    public function save(): void
    {
        if (! $this->canEdit()) {
            return;
        }

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            app(PermalinkSettings::class)->save($data);

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        Notification::make()
            ->success()
            ->title('Permalink settings saved')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill(app(PermalinkSettings::class)->all());
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }
}
