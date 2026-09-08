<?php

namespace App\Filament\Tenant\Resources\ActionsResource\Pages;

use App\Filament\Tenant\Resources\ActionsResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Action;
use App\Models\Plant;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\TemporaryUploadedFile;

class EditActions extends EditRecord
{
    protected static string $resource = ActionsResource::class;

    public function getRecord(): Model
    {
        $record = parent::getRecord();

        $action = Action::query()
            ->whereHas('plants', function ($query) {
                $query->whereHas('indoor', function ($q) {
                    $q->where('tenant_id', auth()->user()->tenant_id);
                });
            })
            ->find($record->id);

        if (!$action) {
            $this->redirect($this->getResource()::getUrl('index'));
            return $record;
        }

        return $action;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!isset($data['data']['observation'])) {
            $data['data']['observation'] = [
                'image' => [],
                'comments' => null,
                'original_filenames' => []
            ];
        }

        // Asegurarse de que image sea un array
        if (!isset($data['data']['observation']['image'])) {
            $data['data']['observation']['image'] = [];
        }

        // Filtrar cualquier valor vacío del array de imágenes
        if (is_array($data['data']['observation']['image'])) {
            $data['data']['observation']['image'] = array_filter($data['data']['observation']['image']);
        }

        // Si no hay imágenes, asegurarse de que sea un array vacío en lugar de [{}]
        if (empty($data['data']['observation']['image'])) {
            $data['data']['observation']['image'] = [];
        }

        $record->update($data);
        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!isset($data['data']['observation'])) {
            $data['data']['observation'] = [
                'image' => [],
                'comments' => null,
                'original_filenames' => [],
            ];
            return $data;
        }

        // Asegurar que los datos de imagen sean consistentes
        if (!isset($data['data']['observation']['image'])) {
            $data['data']['observation']['image'] = [];
        } elseif (is_string($data['data']['observation']['image'])) {
            $data['data']['observation']['image'] = [$data['data']['observation']['image']];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Execute the action trigger
        $this->executeActionTrigger($this->record);
        $this->redirect($this->getResource()::getUrl('index'));

    }

    protected function executeActionTrigger($action)
    {
        $actionClass = $action->action_type->action_class;

        if (class_exists($actionClass)) {
            static::getResource()::executeActionTrigger($action);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            FilamentAction::make('save')
                ->label(__('Guardar'))
                ->icon('heroicon-o-check')
                ->submit('save'),

            FilamentAction::make('cancel')
                ->label(__('Cancelar'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
