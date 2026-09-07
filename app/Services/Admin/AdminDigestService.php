<?php

namespace App\Services\Admin;

/**
 * Recopila novedades para el resumen proactivo que el bot admin le manda
 * al superadmin (jobs fallidos, tenants pendientes, problemas de DB/Redis).
 */
class AdminDigestService
{
    public function __construct(
        private PlatformStatusService $status,
        private TenantAdminService $tenants,
    ) {
    }

    /**
     * @return list<string> Líneas de novedades. Vacío si no hay nada para reportar.
     */
    public function collectFindings(): array
    {
        $summary = $this->status->summary();
        $lines = [];

        if ($summary['db'] === false) {
            $lines[] = '❌ La base de datos no responde.';
        }

        if ($summary['redis'] === false) {
            $lines[] = '❌ Redis no responde.';
        }

        if (($summary['failed_jobs'] ?? 0) > 0) {
            $lines[] = "❌ Hay {$summary['failed_jobs']} job(s) fallido(s) en la cola.";
        }

        if ($summary['pending_tenants'] > 0) {
            $pending = $this->tenants->pendingActivation();
            $names = array_map(
                static fn (array $tenant): string => '#' . $tenant['id'] . ' ' . $tenant['name'],
                $pending
            );
            $lines[] = '⏳ Tenants pendientes de activación (' . count($names) . '): ' . implode(', ', $names);
        }

        return $lines;
    }
}
