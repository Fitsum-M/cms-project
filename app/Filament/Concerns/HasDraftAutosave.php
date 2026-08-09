<?php

namespace App\Filament\Concerns;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Post;
use App\Services\PageService;
use App\Services\PostService;
use App\Support\Settings\GeneralSettings;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Draft auto-save every 60 seconds during active editing (SRS 12.5.2).
 *
 * @mixin EditRecord
 */
trait HasDraftAutosave
{
    public const DRAFT_AUTOSAVE_INTERVAL_SECONDS = 60;

    public ?string $draftAutosaveFingerprint = null;

    public function afterFill(): void
    {
        $this->draftAutosaveFingerprint = $this->fingerprintDraftFormState();
    }

    protected function afterSave(): void
    {
        $this->draftAutosaveFingerprint = $this->fingerprintDraftFormState();
    }

    public function getFooter(): ?View
    {
        if (! $this->shouldRegisterDraftAutosavePoller()) {
            return null;
        }

        return view('filament.components.draft-autosave-poller', [
            'intervalSeconds' => self::DRAFT_AUTOSAVE_INTERVAL_SECONDS,
        ]);
    }

    public function autosaveDraft(): void
    {
        if (! $this->canAutosaveDraft()) {
            return;
        }

        $fingerprint = $this->fingerprintDraftFormState();

        if ($fingerprint === null || $fingerprint === $this->draftAutosaveFingerprint) {
            return;
        }

        $data = $this->form->getStateSnapshot();

        if (! is_array($data)) {
            return;
        }

        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return;
        }

        // Autosave never changes lifecycle status (SRS 12.5.2 — incremental draft save).
        unset($data['status'], $data['confirm_slug_change']);

        try {
            /** @var Model&object{ContentStatus(): ContentStatus} $record */
            $record = $this->getRecord();
            $actor = auth()->user();

            if ($actor === null) {
                return;
            }

            if ($record instanceof Post) {
                app(PostService::class)->autosaveDraft($record, $data, $actor);
            } elseif ($record instanceof Page) {
                app(PageService::class)->autosaveDraft($record, $data, $actor);
            } else {
                return;
            }

            $this->record = $record->fresh() ?? $record;
            $this->draftAutosaveFingerprint = $this->fingerprintDraftFormState();

            Notification::make()
                ->title('Draft auto-saved')
                ->body('Saved at '.(app(GeneralSettings::class)->formatTime(now()) ?? now()->format('H:i')))
                ->success()
                ->duration(2500)
                ->send();
        } catch (ValidationException $exception) {
            // Quietly skip incomplete drafts (title already checked; other rules may still fire).
            report($exception);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Draft auto-save failed')
                ->body('Your changes are still in the editor — try saving manually.')
                ->danger()
                ->send();
        }
    }

    protected function shouldRegisterDraftAutosavePoller(): bool
    {
        $record = $this->getRecord();

        return ($record instanceof Post || $record instanceof Page)
            && ! $record->trashed()
            && $record->contentStatus() === ContentStatus::Draft;
    }

    protected function canAutosaveDraft(): bool
    {
        $record = $this->getRecord();
        $user = auth()->user();

        if ($user === null || (! $record instanceof Post && ! $record instanceof Page)) {
            return false;
        }

        if ($record->trashed() || $record->contentStatus() !== ContentStatus::Draft) {
            return false;
        }

        return $user->can('update', $record);
    }

    protected function fingerprintDraftFormState(): ?string
    {
        try {
            $state = $this->form->getRawState();
        } catch (Throwable) {
            return null;
        }

        if (! is_array($state)) {
            $state = Arr::wrap($state);
        }

        // Ignore volatile UI-only keys.
        unset($state['confirm_slug_change']);

        ksort($state);

        return hash('xxh128', (string) json_encode($state));
    }
}
