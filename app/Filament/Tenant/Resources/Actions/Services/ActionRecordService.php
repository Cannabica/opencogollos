<?php

namespace App\Filament\Tenant\Resources\Actions\Services;

use App\Models\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ActionRecordService
{
    public function handleRecordCreation(array $data): Action
    {
        Log::debug('🏗️ Iniciando creación de acción', [
            'tipo' => $data['action_type_id'],
            'plantas_seleccionadas' => $data['plants'] ?? []
        ]);

        return DB::transaction(function () use ($data) {
            try {
                // 1. Validar y preparar plantas
                $plantsToAttach = [];
                if (!empty($data['plants'])) {
                    $plantsToAttach = $this->validatePlants($data['plants'], $data['indoor_id']);
                    Log::debug('🌱 Plantas validadas para asociar', [
                        'count' => count($plantsToAttach),
                        'ids' => $plantsToAttach
                    ]);
                }

                // 2. Crear la acción
                $record = Action::create([
                    'action_type_id' => $data['action_type_id'],
                    'action_date' => $data['action_date'],
                    'indoor_id' => $data['indoor_id'],
                    'tenant_id' => auth()->user()->tenant_id,
                    'data' => $data['data'] ?? [],
                ]);

                // 3. Asociar plantas inmediatamente
                if (!empty($plantsToAttach)) {
                    DB::table('action_plant')->insert(
                        collect($plantsToAttach)->map(function ($plantId) use ($record) {
                            return [
                                'action_id' => $record->id,
                                'plant_id' => $plantId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        })->toArray()
                    );

                    // 4. Recargar el modelo con las relaciones
                    $record->refresh();
                    
                    Log::debug('✅ Plantas asociadas correctamente', [
                        'action_id' => $record->id,
                        'plantas_count' => count($plantsToAttach),
                        'plantas_ids' => $plantsToAttach
                    ]);
                }

                // 5. Verificación final
                $finalPlantCount = $record->plants()->count();
                Log::debug('🔍 Verificación final de acción', [
                    'action_id' => $record->id,
                    'plantas_asociadas' => $finalPlantCount,
                    'tipo_accion' => $data['action_type_id']
                ]);

                if ($finalPlantCount === 0 && !empty($data['plants'])) {
                    throw new \Exception('No se pudieron asociar las plantas seleccionadas');
                }

                return $record;

            } catch (QueryException $e) {
                Log::error('💥 Error de base de datos en creación', [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'no disponible',
                    'bindings' => $e->getBindings() ?? []
                ]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('💥 Error general en creación', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    private function validatePlants(array $plantIds, int $indoorId): array
    {
        try {
            // Consulta directa para validar plantas
            $validPlants = DB::table('plants AS p')
                ->select('p.id')
                ->whereIn('p.id', $plantIds)
                ->where('p.indoor_id', $indoorId)
                ->get()
                ->pluck('id')
                ->toArray();

            if (count($validPlants) !== count($plantIds)) {
                Log::warning('⚠️ Algunas plantas no son válidas', [
                    'solicitadas' => $plantIds,
                    'validadas' => $validPlants,
                    'indoor_id' => $indoorId
                ]);
            }

            return $validPlants;

        } catch (\Exception $e) {
            Log::error('💥 Error validando plantas', [
                'error' => $e->getMessage(),
                'indoor_id' => $indoorId,
                'plantas_solicitadas' => $plantIds
            ]);
            throw $e;
        }
    }

    public function handleRecordUpdate(Action $record, array $data): Action
    {
        Log::debug('🔄 Iniciando actualización de acción', [
            'action_id' => $record->id,
            'data' => $data
        ]);

        return DB::transaction(function () use ($record, $data) {
            $record->update([
                'action_date' => $data['action_date'],
                'data' => $data['data'] ?? $record->data,
            ]);

            Log::debug('✅ Acción actualizada', ['action_id' => $record->id]);

            return $record;
        });
    }
} 