<?php

namespace App\Services\Admin;

use App\Models\Action;
use App\Models\CropPlan;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\TelegramUserTenant;
use App\Models\Tenant;
use App\Models\User;
use Throwable;

/**
 * Consultas de tenants y métricas globales para el bot de administración.
 */
class TenantAdminService
{
    /**
     * Listado acotado de tenants para el bot.
     *
     * @return list<array{id: int, name: ?string, email: ?string, active: bool, users: int}>
     */
    public function list(int $limit = 20): array
    {
        return Tenant::query()
            ->withCount('users')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'active' => (bool) $tenant->active,
                'users' => $tenant->users_count,
            ])
            ->all();
    }

    public function totalTenants(): int
    {
        return Tenant::query()->count();
    }

    /**
     * @return list<array{id: int, name: ?string, email: ?string}>
     */
    public function pendingActivation(): array
    {
        return Tenant::query()
            ->where('active', false)
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
            ])
            ->all();
    }

    /**
     * Detalle de un tenant: datos + conteos.
     *
     * @return array{id: int, name: ?string, email: ?string, active: bool,
     *               created_at: ?string, activated_at: ?string, owner: ?string,
     *               web_users: int, telegram_users: int, indoors: int,
     *               plants: int, seeds: int, crop_plans: int}|null
     */
    public function detail(int $id): ?array
    {
        $tenant = Tenant::query()->with('owner')->find($id);

        if (! $tenant) {
            return null;
        }

        $indoorsCount = Indoor::query()->where('tenant_id', $id)->count();
        $plantsCount = Indoor::query()
            ->where('tenant_id', $id)
            ->withCount('plants')
            ->get()
            ->sum('plants_count');

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'active' => (bool) $tenant->active,
            'created_at' => $this->formatDate($tenant->created_at),
            'activated_at' => $this->formatDate($tenant->activated_at),
            'owner' => $tenant->owner?->name,
            'web_users' => User::query()->where('tenant_id', $id)->count(),
            'telegram_users' => TelegramUserTenant::query()->where('tenant_id', $id)->count(),
            'indoors' => $indoorsCount,
            'plants' => $plantsCount,
            'seeds' => Seed::query()->where('tenant_id', $id)->count(),
            'crop_plans' => CropPlan::query()->where('tenant_id', $id)->count(),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('d/m/Y H:i');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Conteos globales de la plataforma.
     *
     * @return array{tenants: int, tenants_active: int, users: int, indoors: int,
     *               plants: int, actions: int, seeds: int, crop_plans: int, telegram_users: int}
     */
    public function globalMetrics(): array
    {
        return [
            'tenants' => Tenant::query()->count(),
            'tenants_active' => Tenant::query()->where('active', true)->count(),
            'users' => User::query()->count(),
            'indoors' => Indoor::query()->count(),
            'plants' => Plant::query()->count(),
            'actions' => Action::query()->count(),
            'seeds' => Seed::query()->count(),
            'crop_plans' => CropPlan::query()->count(),
            'telegram_users' => TelegramUserTenant::query()->count(),
        ];
    }
}
