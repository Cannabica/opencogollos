<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Chequeos de salud de la plataforma para el bot de administración.
 * Nunca tira excepciones: cada chequeo devuelve su estado o null si no
 * se pudo determinar.
 */
class PlatformStatusService
{
    public function checkDatabase(): bool
    {
        try {
            DB::select('select 1');
            return true;
        } catch (Throwable $e) {
            Log::warning('Admin status: chequeo de DB falló', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function checkRedis(): ?bool
    {
        // Solo tiene sentido chequear Redis si el stack lo usa (cache, cola o
        // sesiones). Si no, devolvemos null (no aplica) y no genera ruido.
        $usesRedis = config('cache.default') === 'redis'
            || config('queue.default') === 'redis'
            || config('session.driver') === 'redis';

        if (! $usesRedis) {
            return null;
        }

        try {
            Redis::connection()->ping();
            return true;
        } catch (Throwable $e) {
            Log::warning('Admin status: chequeo de Redis falló', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function queueDriver(): string
    {
        return (string) config('queue.default', 'sync');
    }

    public function failedJobsCount(): ?int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            Log::debug('Admin status: tabla failed_jobs no disponible', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function pendingTenantCount(): int
    {
        return Tenant::query()->where('active', false)->count();
    }

    /**
     * @return array{db: bool, redis: ?bool, queue_driver: string, failed_jobs: ?int, pending_tenants: int}
     */
    public function summary(): array
    {
        return [
            'db' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue_driver' => $this->queueDriver(),
            'failed_jobs' => $this->failedJobsCount(),
            'pending_tenants' => $this->pendingTenantCount(),
        ];
    }
}
