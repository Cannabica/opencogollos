<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Contexto de tenant de la REQUEST actual, para las consultas que corren sin sesión
 * (webhook de Telegram, jobs, comandos artisan, seeders).
 *
 * Por qué existe: el `TenantScope` filtraba con `auth()->user()`, y en el webhook del bot NO hay sesión.
 * Con el scope "no-op" cuando no hay usuario, cualquier `Plant::find($id)` devolvía datos de otro
 * tenant: `/actiondetails`, `/plantdetails` y los callbacks `select_indoor:` / `repeat_irrigation:`
 * mostraban (y escribían sobre) datos ajenos. Ver `hotfix` del 2026-09-28 y el §5.2 de
 * `board/epicas/2026-09-18-revision-fugas-dashboard-widgets.md`.
 *
 * Reglas de resolución (las aplica `TenantScope::apply`):
 *   1. Contexto explícito (`use()` / `useAll()`) → manda ese.
 *   2. Sin contexto explícito → el usuario logueado (`tenant_id`), como el panel Filament.
 *      Un usuario SIN tenant (superadmin) queda exento: ve todo (es su trabajo).
 *   3. Sin usuario y sin contexto:
 *      - HTTP (un request sin sesión, p.ej. el webhook) → **falla cerrado: no devuelve nada**.
 *      - consola (seeders, artisan, cron) → sin filtro, que es el comportamiento histórico de esos
 *        caminos y lo que necesitan los seeders y el digest del admin.
 *
 * Corolario operativo: el bot de Telegram (que no tiene sesión) TIENE que fijar el contexto con
 * `use($tenantId)` después de resolver la asociación del chat — lo hace
 * `App\Telegram\Commands\ChecksTelegramExpiration::checkTelegramAssociation()`. Si no lo hiciera, no
 * vería nada (que es exactamente la falla segura que se buscó).
 */
final class TenantContext
{
    private static ?int $tenantId = null;

    private static bool $explicit = false;

    /** Filtra por este tenant durante el resto del request. */
    public static function use(int $tenantId): void
    {
        self::$tenantId = $tenantId;
        self::$explicit = true;
    }

    /** Contexto de servicio: sin filtro de tenant (superadmin / panel admin del bot / consola). */
    public static function useAll(): void
    {
        self::$tenantId = null;
        self::$explicit = true;
    }

    /** Vuelve al comportamiento por defecto (usuario logueado → consola → falla cerrado). */
    public static function forget(): void
    {
        self::$tenantId = null;
        self::$explicit = false;
    }

    public static function isExplicit(): bool
    {
        return self::$explicit;
    }

    /**
     * Con qué tenant filtrar.
     *
     * @return int|false|null int = ese tenant; null = sin filtro (contexto de servicio); false = no hay
     *                        contexto → el scope no devuelve nada.
     */
    public static function resolve(): int|false|null
    {
        if (self::$explicit) {
            return self::$tenantId;
        }

        $user = Auth::user();

        if ($user !== null) {
            // Usuario sin tenant = superadmin: exento del scope (el panel superadmin necesita ver todo).
            return $user->tenant_id === null ? null : (int) $user->tenant_id;
        }

        // Consola: seeders, artisan y cron (el digest del admin) dependen de no filtrar.
        // Los tests quedan afuera a propósito, para que puedan afirmar la falla cerrada.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return false;
    }
}
