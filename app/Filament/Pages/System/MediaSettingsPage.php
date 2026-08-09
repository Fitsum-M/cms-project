<?php

namespace App\Filament\Pages\System;

use App\Enums\Permission;
use App\Support\Settings\MediaSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
class MediaSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'Media';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Media Settings';

    protected static ?string $slug = 'system/media';

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
        $foldersReady = MediaSettings::foldersTableReady();
        $folderHelper = $foldersReady
            ? 'Folder used as the default destination for new uploads.'
            : 'Create folders under Digital Asset Management → Folders to enable this setting.';

        return $schema
            ->components([
                Section::make('Image sizes')
                    ->description('Max dimensions for generated thumbnail, medium, and large conversions. Originals are always preserved.')
                    ->schema([
                        TextInput::make(MediaSettings::THUMBNAIL_WIDTH)
                            ->label('Thumbnail Width')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                        TextInput::make(MediaSettings::THUMBNAIL_HEIGHT)
                            ->label('Thumbnail Height')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                        TextInput::make(MediaSettings::MEDIUM_WIDTH)
                            ->label('Medium Width')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                        TextInput::make(MediaSettings::MEDIUM_HEIGHT)
                            ->label('Medium Height')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                        TextInput::make(MediaSettings::LARGE_WIDTH)
                            ->label('Large Width')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                        TextInput::make(MediaSettings::LARGE_HEIGHT)
                            ->label('Large Height')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix('px'),
                    ])
                    ->columns(2),
                Section::make('Uploads')
                    ->schema([
                        TextInput::make(MediaSettings::UPLOAD_MAX_FILE_SIZE_MB)
                            ->label('Upload Max File Size')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1024)
                            ->suffix('MB'),
                        Select::make(MediaSettings::DEFAULT_UPLOAD_FOLDER_ID)
                            ->label('Default Upload Folder')
                            ->options(fn (): array => MediaSettings::folderOptions())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Library root —')
                            ->helperText($folderHelper)
                            ->rules($this->folderReferenceRules()),
                        CheckboxList::make(MediaSettings::ALLOWED_FILE_TYPES)
                            ->label('Allowed File Types')
                            ->options(MediaSettings::fileTypeOptions())
                            ->required()
                            ->columns(2)
                            ->bulkToggleable()
                            ->helperText('Permitted extensions from SRS 14.2. At least one type must remain selected.'),
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

            app(MediaSettings::class)->save($data);

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
            ->title('Media settings saved')
            ->send();
    }

    protected function fillForm(): void
    {
        $this->form->fill(app(MediaSettings::class)->all());
    }

    protected function canEdit(): bool
    {
        return auth()->user()?->can(Permission::SettingsEdit->value) ?? false;
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function folderReferenceRules(): array
    {
        if (! MediaSettings::foldersTableReady()) {
            return ['nullable'];
        }

        return [
            'nullable',
            'integer',
            Rule::exists('folders', 'id'),
        ];
    }
}
