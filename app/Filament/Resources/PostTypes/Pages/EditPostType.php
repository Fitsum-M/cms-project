<?php

namespace App\Filament\Resources\PostTypes\Pages;

use App\Filament\Resources\PostTypes\PostTypeResource;
use App\Models\PostType;
use App\Services\PostTypeService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPostType extends EditRecord
{
    protected static string $resource = PostTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(function (PostType $record): void {
                    try {
                        app(PostTypeService::class)->delete($record);
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete post type')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Delete blocked.')
                            ->send();

                        throw $exception;
                    }
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PostType $record */
        $record = $this->getRecord();
        $data['custom_taxonomy_ids'] = $record->customTaxonomyIds();

        $schema = $record->default_schema_type;
        $known = array_keys(\App\Support\Settings\SeoDefaultsSettings::schemaTypeOptions());

        if (filled($schema) && ! in_array($schema, $known, true)) {
            $data['default_schema_type'] = 'Custom';
            $data['custom_schema_type'] = $schema;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PostType $record */
        return app(PostTypeService::class)->update($record, $data);
    }
}
