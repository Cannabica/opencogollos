<?php

namespace App\Telegram\Commands;

use App\Models\Plant;
use App\Models\Tenant;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;
use Telegram\Bot\Objects\Update;

class PlantDetailsCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'plantdetails';
    protected string $pattern = '{plant_id?:\d+}';
    protected string $description = 'Muestra detalles de una planta específica';

    public function handle()
    {
        $telegramUser = $this->getUpdate()->getMessage()->getFrom();
        $telegramUserId = $this->getUpdate()->getCallbackQuery()
            ? $this->getUpdate()->getCallbackQuery()->getFrom()->getId()
            : $telegramUser->getId();

        Log::info('PlantDetailsCommand triggered', [
            'telegram_user_id' => $telegramUserId,
        ]);

        // Validate tenant association
        $association = $this->checkTelegramAssociation($telegramUserId);
        if (!$association) {
            Log::warning('Unauthorized access attempt', ['telegram_user_id' => $telegramUserId]);
            return;
        }

        $plantId = $this->getUpdate()->getCallbackQuery()
            ? explode(':', $this->getUpdate()->getCallbackQuery()->getData())[1]
            : $this->argument('plant_id');
            
        if (!$plantId || !is_numeric($plantId)) {
            $this->replyWithMessage([
                'text' => '❌ <b>Error:</b> Debes especificar un ID de planta válido',
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $plant = Plant::where('id', $plantId)
            ->with(['indoor', 'seedType'])
            ->first();

        // Get last 5 actions with their types
        $actions = \App\Models\Action::whereHas('plants', function($query) use ($plantId) {
                $query->where('plant_id', $plantId);
            })
            ->with('action_type')
            ->latest()
            ->take(5)
            ->get();

        if (!$plant) {
            $this->replyWithMessage([
                'text' => "❌ <b>Planta no encontrada</b>\nNo existe una planta con ID: $plantId",
                'parse_mode' => 'HTML',
            ]);
            Log::warning('Plant not found', ['plant_id' => $plantId]);
            return;
        }

        $indoorName = $plant->indoor ? $plant->indoor->name : 'Sin ubicación';
        $seedName = $plant->seedType ? $plant->seedType->name : 'Desconocida';
        $floweringTime = $plant->seedType && $plant->seedType->flowering_time
            ? htmlspecialchars($plant->seedType->flowering_time) . ' días'
            : 'No especificado';
        $thcRatio = $plant->seedType && $plant->seedType->ratio_thc
            ? htmlspecialchars($plant->seedType->ratio_thc) . '%'
            : 'No especificado';
        $cbdRatio = $plant->seedType && $plant->seedType->ratio_cbd
            ? htmlspecialchars($plant->seedType->ratio_cbd) . '%'
            : 'No especificado';

        // Prepare all values with proper escaping
        $id = htmlspecialchars($plant->id);
        $name = htmlspecialchars($plant->name);
        $seedName = htmlspecialchars($seedName);
        $state = $plant->state ? htmlspecialchars($plant->state) : 'No especificado';
        $indoorName = htmlspecialchars($indoorName);
        $flowerpot = $plant->flowerpot ? htmlspecialchars($plant->flowerpot) : 'No especificada';
        
        $germinationDate = $plant->germination_date 
            ? htmlspecialchars(date('d/m/Y', strtotime($plant->germination_date))) 
            : 'No especificada';

        // Build message parts
        // Format array fields (already cast to arrays by model)
        $baseFloor = !empty($plant->base_floor)
            ? htmlspecialchars(
                is_array($plant->base_floor)
                    ? implode(',  ', array_map('trim', $plant->base_floor))
                    : str_replace(['["', '"]', '"'], '', $plant->base_floor)
                    ,ENT_QUOTES, 'UTF-8')
            : 'No especificado';
        $soilEnrichment = !empty($plant->soil_enrichment)
            ? htmlspecialchars(
                is_array($plant->soil_enrichment)
                    ? implode(',  ', array_map('trim', $plant->soil_enrichment))
                    : str_replace(['["', '"]', '"'], '', $plant->soil_enrichment)
                    ,ENT_QUOTES, 'UTF-8')
            : 'No especificado';


        $capacity = $plant->capacity ? htmlspecialchars($plant->capacity) : 'No especificado';

        $messageParts = [
            "==<b>🌱 $name </b>==",
            "",
            "<b>🌾 Sema:</b> $seedName",
            "<b>├─ THC:</b> $thcRatio",
            "<b>├─ CBD:</b> $cbdRatio",
            "<b>├─ Tiempo de floración:</b> $floweringTime",
            "<b>📋 Estado:</b> $state",
            "<b>📍 Ubicación:</b> $indoorName",
            "<b>├─ Maceta:</b> $flowerpot",
            "<b>├─ Capacidad:</b> {$capacity}L",
            "<b>├─ Sustrato base:</b> $baseFloor",
            "<b>├─ Enriquecimiento:</b> $soilEnrichment",
            "<b>📅 Fecha de germinación:</b> $germinationDate",
        ];

        // Add action statistics
        $actionStats = \App\Models\Action::whereHas('plants', function($query) use ($plantId) {
                $query->where('plant_id', $plantId);
            })
            ->selectRaw('action_type_id, count(*) as count')
            ->groupBy('action_type_id')
            ->with('action_type')
            ->get();

        $totalActions = $actionStats->sum('count');
        $actionStatsText = [];
        
        foreach ($actionStats as $stat) {
            $percentage = $totalActions > 0 ? round(($stat->count / $totalActions) * 100) : 0;
            $actionStatsText[] = "<b>{$stat->action_type->name}:</b> {$stat->count} ({$percentage}%)";
        }

        // Format last 5 actions
        $actionLines = [];
        foreach ($actions as $action) {
            $date = $action->action_date->format('d/m/Y H:i');
            $actionLines[] = "<b>⏱ {$date}:</b> " . 
                mb_strimwidth("{$action->type_name} - {$action->detalle_accion}", 0, 80, "...");
        }

        // Add action sections to message
        $messageParts[] = "";
        $messageParts[] = "<b>📊 Estadísticas de acciones ({$totalActions} total):</b>";
        $messageParts = array_merge($messageParts, $actionStatsText);
        $messageParts[] = "";
        $messageParts[] = "<b>⏳ Últimas acciones:</b>";
        $messageParts = array_merge($messageParts, $actionLines);

        $message = implode("\n", $messageParts);

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        Log::info('Plant details sent', [
            'plant_id' => $plantId,
            'tenant_id' => $association->tenant_id
        ]);
    }
}