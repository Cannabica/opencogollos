<?php

namespace App\Telegram\Commands;

use App\Models\CropPlan;
use Log;
use Telegram\Bot\Commands\Command;
use Illuminate\Support\Facades\Http;
use App\Services\TenantTokenService;
use App\Models\Tenant;
use App\Telegram\Commands\ChecksTelegramExpiration;

class TenantInfoCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'tenantinfo';
    protected string $description = 'Obtiene información del tenant actual';

    public function handle()
    {
        try {
            $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();

            // Check for valid (non-expired) association
            $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
            if (!$existingAssociation) {
                return;
            }

            $tenant = Tenant::find($existingAssociation->tenant_id);

            if (!$tenant) {
                $this->replyWithMessage([
                    'text' => '❌ Primero debes autenticarte con /auth TU_TOKEN',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            if ($tenant) {
                $message = "ℹ️ <b>Información del Tenant</b>\n\n";
                $message .= "🔹 <b>Nombre</b>: " . ($tenant->name ? htmlspecialchars($tenant->name, ENT_QUOTES, 'UTF-8') : 'N/A') . "\n";
                $message .= "🔹 <b>Creado</b>: " . ($tenant->created_at ? htmlspecialchars($tenant->created_at->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') : 'N/A') . "\n";
                
                // Get all associated Telegram users
                $telegramUsers = \App\Models\TelegramUserTenant::where('tenant_id', $tenant->id)
                    ->select('telegram_username','telegram_user_id')
                    ->distinct()
                    ->get();
                
                $message .= "🔹 <b>Usuarios Telegram</b> (" . $telegramUsers->count() . "):\n";
                foreach ($telegramUsers as $user) {
                    $message .= "    👤 " . ($user->telegram_username ? htmlspecialchars($user->telegram_username, ENT_QUOTES, 'UTF-8') : 'N/A') .
                                " (" . ($user->telegram_user_id ? htmlspecialchars($user->telegram_user_id, ENT_QUOTES, 'UTF-8') : 'N/A') . ")\n";
                }

                // Get all web users
                $webUsers = \App\Models\User::where('tenant_id', $tenant->id)
                    ->select('name', 'email')
                    ->get();
                
                $message .= "🔹 <b>Usuarios Web</b> (" . $webUsers->count() . "):\n";
                foreach ($webUsers as $user) {
                    $message .= "    👤 " . ($user->name ? htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') : 'N/A') .
                                " (" . ($user->email ? htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') : 'N/A') . ")\n";
                }
                $message .= "\n";

                // Indoors with debug logging
                \Log::debug('Loading indoors for tenant', ['tenant_id' => $tenant->id]);
                $indoors = $tenant->indoors()->with([
                    'plants' => function($query) {
                        $query->where('state', '!=', 'muerta')->with('seedType');
                    },
                    'actions' => function ($q) {
                        $q->latest()->limit(5);
                    }
                ])->get();

                // Summary
                $totalPlants = $indoors->sum(function ($indoor) {
                    return $indoor->plants->count();
                });

                $message .= "🏘️ <b>Indoors</b>: " . $indoors->count() . "\n";
                $seeds = \App\Models\Seed::where('tenant_id', $tenant->id)->get();

                $message .= "🌰 <b>Semillas propias</b>: " . $seeds->count() . "\n";
                // Crop plans
                $cropPlans = CropPlan::where('tenant_id', $tenant->id)->get();
                $message .= "📅 <b>Planes de cultivo propios</b> (" . $cropPlans->count() . "):\n\n";
                
                $message .= "🔹 Plantas en total: " . $totalPlants . "\n";

                foreach ($indoors as $indoor) {
                    $message .= "    🏠 " . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8') . "\n    ├─ 🌱 " . $indoor->plants->count() . " plantas\n";
                }

                $this->replyWithMessage([
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ]);
            } else {
                $this->replyWithMessage([
                    'text' => '❌ No se encontró información del tenant.',
                    'parse_mode' => 'HTML'
                ]);
            }
        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error de conexión: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
}
