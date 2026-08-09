<?php

namespace App\Filament\Pages\Dam;

use App\Enums\Permission;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\FolderService;
use App\Services\MediaUploadService;
use App\Support\Settings\MediaSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
use Illuminate\Validation\ValidationException;
use Throwable;
use UnitEnum;

/**
 * M.01 — Upload workflow (drag-drop, file picker, bulk upload, progress).
 *
 * @property-read Schema $form
 */
class UploadMedia extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Digital Asset Management';

    protected static ?string $navigationLabel = 'Upload Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Upload Media';

    protected static ?string $slug = 'dam/upload';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::MediaUpload->value) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'files' => [],
            'folder_id' => app(MediaSettings::class)->defaultUploadFolderId(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('create')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(MediaSettings::class);
        $allowed = $settings->allowedFileTypes();
        $maxMb = $settings->uploadMaxFileSizeMb();

        return $schema
            ->components([
                Section::make('Upload files')
                    ->description('Drag and drop files here, or use the file picker. Multiple files are uploaded in one batch with progress shown for each file.')
                    ->schema([
                        Select::make('folder_id')
                            ->label('Destination folder')
                            ->options(fn (): array => app(FolderService::class)->options())
                            ->searchable()
                            ->nullable()
                            ->placeholder('— Unfiled —')
                            ->helperText('Defaults to the Media Settings default upload folder when set.'),
                        FileUpload::make('files')
                            ->label('Files')
                            ->multiple()
                            ->required()
                            ->panelLayout('grid')
                            ->imagePreviewHeight('120')
                            ->uploadingMessage('Uploading…')
                            ->acceptedFileTypes($settings->acceptedMimeTypes())
                            ->maxSize($settings->uploadMaxFileSizeKb())
                            ->maxFiles(50)
                            ->storeFiles(false)
                            ->visibility('private')
                            ->helperText(
                                'Allowed: '.implode(', ', $allowed).". Max {$maxMb} MB per file."
                            )
                            ->columnSpanFull(),
                    ]),
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
            ->livewireSubmitHandler('upload')
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
            Action::make('upload')
                ->label('Upload')
                ->submit('upload')
                ->keyBindings(['mod+s']),
            Action::make('openLibrary')
                ->label('Open library')
                ->url(MediaAssetResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function upload(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $files = $data['files'] ?? [];

            if (! is_array($files)) {
                $files = [];
            }

            $assets = app(MediaUploadService::class)->uploadMany(
                $files,
                auth()->user(),
                isset($data['folder_id']) && $data['folder_id'] !== ''
                    ? (int) $data['folder_id']
                    : null,
                applyDefaultFolder: false,
            );

            $this->commitDatabaseTransaction();
        } catch (ValidationException $exception) {
            $this->rollBackDatabaseTransaction();

            Notification::make()
                ->danger()
                ->title('Upload failed')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Could not upload files.')
                ->send();

            throw $exception;
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $count = $assets->count();

        Notification::make()
            ->success()
            ->title($count === 1 ? '1 file uploaded' : "{$count} files uploaded")
            ->body('Files are now available in the media library.')
            ->send();

        $this->redirect(MediaAssetResource::getUrl('index'));
    }
}
