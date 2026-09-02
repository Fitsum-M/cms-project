<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use App\Services\FolderService;
use App\Services\MediaUploadService;
use App\Support\Settings\MediaSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Bulk upload workflow as MediaAssetResource create (formerly Dam\UploadMedia).
 *
 * @property-read Schema $form
 */
class CreateMediaAsset extends Page
{
    use CanUseDatabaseTransactions;

    protected static string $resource = MediaAssetResource::class;

    protected static ?string $title = 'Upload Media';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function getTitle(): string|Htmlable
    {
        return 'Upload Media';
    }

    public function mount(): void
    {
        abort_unless(static::getResource()::canCreate(), 403);

        $this->form->fill([
            'files' => [],
            'folder_id' => app(MediaSettings::class)->defaultUploadFolderId(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openLibrary')
                ->label('Open library')
                ->icon('heroicon-o-photo')
                ->url(MediaAssetResource::getUrl('index'))
                ->color('gray'),
        ];
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
                    ->description('Drag and drop files here, or use the file picker. Wait until each file finishes uploading (progress completes), then click Upload.')
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
            ->livewireSubmitHandler('submitUpload')
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
            Action::make('submitUpload')
                ->label('Upload')
                ->icon('heroicon-o-arrow-up-tray')
                ->submit('submitUpload')
                ->keyBindings(['mod+s']),
        ];
    }

    public function submitUpload(): void
    {
        $this->authorize('create', MediaAsset::class);

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

        $folderId = isset($data['folder_id']) && $data['folder_id'] !== ''
            ? (int) $data['folder_id']
            : null;

        $this->redirect($this->libraryUrlForFolder($folderId));
    }

    protected function libraryUrlForFolder(?int $folderId): string
    {
        $parameters = [
            'filters' => [
                'folder_scope' => [
                    'value' => $folderId === null ? 'unfiled' : (string) $folderId,
                ],
            ],
        ];

        return MediaAssetResource::getUrl('index', $parameters);
    }
}
