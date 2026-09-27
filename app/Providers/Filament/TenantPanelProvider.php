<?php

namespace App\Providers\Filament;

use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Tenant;
use App\Filament\Pages\Dashboard;
use App\Models\Tenant as T;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Navigation\MenuItem;
use App\Filament\Tenant\Widgets\ProductNotificationsWidget;
use App\Http\Middleware\LogUsageMiddleware;

use Rupadana\ApiService\ApiServicePlugin;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // CSS del dashboard/panel que vivía en public/css/custom.css (legacy), migrado a
        // entrada Vite: resources/css/filament/tenant/dashboard.css.
        // ⚠️ Registro TOLERANTE a un manifest incompleto: si el manifest de Vite no tiene la
        // entrada (build de la imagen desactualizado, cache del CI), NO debe romper — un
        // Vite::asset() sin la key lanza excepción al boot del provider y tumba TODOS los
        // comandos artisan (incluido migrate del entrypoint → contenedor unhealthy). Si el
        // asset falta, se registra sin el CSS custom (la UI queda con el default, no caída).
        $dashboardCssUrl = null;
        $manifestPath = public_path('build/manifest.json');
        if (is_file($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $entry = is_array($manifest) ? ($manifest['resources/css/filament/tenant/dashboard.css'] ?? null) : null;
            if (is_array($entry) && isset($entry['file'])) {
                // URL ABSOLUTA (asset()): Filament respeta las URLs con host como asset
                // externo (link directo al build). Un path relativo ("/build/...") lo trata
                // como asset propio y lo reescribe a su endpoint /css/{panel}/{id}.css → 404
                // y el CSS no carga (bug detectado en prod con la UI del dashboard rota).
                $dashboardCssUrl = asset('build/' . $entry['file']);
            }
        }

        $assets = [];
        if ($dashboardCssUrl !== null) {
            $assets[] = Css::make('custom-css', $dashboardCssUrl);
        }

        FilamentAsset::register($assets);

        return $panel
            ->id('tenant')
            ->plugin(
                ApiServicePlugin::make()
            )
            ->path('tenant')
            ->font('Inter')
            ->login()
            ->registration(\App\Filament\Tenant\Pages\Registration::class)
            // ⚠️ La clase vive FUERA de `app/Filament/Tenant/Pages` a propósito: `discoverPages` la
            // levantaba como página común y se quedaba con la ruta `password-reset/reset`, dejando la
            // del vendor atendiendo el link del mail (por eso los cambios de esta clase no corrían:
            // 2026-09-27). Así la registra sólo el panel, como página de autenticación.
            ->passwordReset(\App\Filament\Tenant\Auth\ResetPassword::class)
            ->authGuard('web')
            ->default()
            ->favicon(asset(path: 'images/favicon.png'))
            // C4e (WS9/T9.5): el nombre de marca del panel es el MISMO que el de los
            // logos y la PWA (platform.brand_name con fallback a app.name). Sin esto,
            // los titulos de pagina ("Acceso - X", "Dashboard - X") seguian mostrando
            // app.name y la instalacion mostraba dos nombres.
            ->brandName(fn() => config('platform.brand_name') ?: config('app.name'))
            ->brandLogo(fn() => view('filament.admin.logo'))
            ->darkModeBrandLogo(fn() => view('filament.admin.logo-darkmode'))
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->viteTheme('resources/css/filament/tenant/theme.css')
            ->pages([
                Dashboard::class,
                \App\Filament\Tenant\Pages\TenantPage::class,
                \App\Filament\Tenant\Pages\ActivationPending::class,
                \App\Filament\Tenant\Pages\PasswordChange::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->colors([
                'primary' => [
                    50 => '239, 245, 252', // #EFF5FC
                    100 => '220, 233, 250', // #DCE9FA
                    200 => '187, 213, 244', // #BBD5F4
                    300 => '142, 186, 238', // #8EBAEE
                    400 => '94, 156, 230',  // #5E9CE6 (dark)
                    500 => '62, 128, 203',  // #3E80CB
                    600 => '35, 105, 181',  // #2369B5 (light — marca)
                    700 => '26, 79, 136',   // #1A4F88
                    800 => '19, 58, 100',   // #133A64
                    900 => '13, 40, 69',    // #0D2845
                    950 => '8, 23, 40',     // #081728
                ],
                'secondary' => [
                    50 => '240, 250, 242',  // #F0FAF2
                    100 => '225, 245, 229', // #E1F5E5
                    200 => '194, 235, 215', // #C2EBD7
                    300 => '163, 225, 202', // #A3E1CA
                    400 => '132, 215, 189', // #84D7BD
                    500 => '101, 205, 175', // #65CDAF
                    600 => '82, 167, 140',  // #52A78C
                    700 => '63, 129, 105',  // #3F8169
                    800 => '44, 91, 70',    // #2C5B46
                    900 => '25, 53, 35',    // #193523
                    950 => '12, 26, 17',    // #0C1A11
                ],
                'tertiary' => [
                    50 => '233, 242, 233',  // #E9F2E9
                    100 => '212, 229, 212', // #D4E5D4
                    200 => '185, 215, 185', // #B9D7B9
                    300 => '158, 201, 158', // #9EC99E
                    400 => '131, 187, 131', // #83BB83
                    500 => '104, 173, 104', // #68AD68
                    600 => '85, 138, 85',   // #558A55
                    700 => '66, 103, 66',   // #426742
                    800 => '47, 68, 47',    // #2F442F
                    900 => '28, 33, 28',    // #1C211C
                    950 => '14, 16, 14',    // #0E100E
                ],
                'dark' => [
                    50 => '248, 250, 252',  // #F8FAFC (slate neutro — fuera el violeta)
                    100 => '241, 245, 249', // #F1F5F9
                    200 => '226, 232, 240', // #E2E8F0
                    300 => '203, 213, 225', // #CBD5E1
                    400 => '148, 163, 184', // #94A3B8
                    500 => '100, 116, 139', // #64748B
                    600 => '71, 85, 105',   // #475569
                    700 => '51, 65, 85',    // #334155
                    800 => '30, 41, 59',    // #1E293B
                    900 => '15, 23, 42',    // #0F172A
                    950 => '2, 6, 23',      // #020617
                ],
                'accent' => [
                    50 => '254, 242, 242',  // #FEF2F2 (red danger de marca — fuera el rosa)
                    100 => '254, 226, 226', // #FEE2E2
                    200 => '254, 202, 202', // #FECACA
                    300 => '252, 165, 165', // #FCA5A5
                    400 => '248, 113, 113', // #F87171
                    500 => '239, 68, 68',   // #EF4444
                    600 => '220, 38, 38',   // #DC2626
                    700 => '185, 28, 28',   // #B91C1C
                    800 => '153, 27, 27',   // #991B1B
                    900 => '127, 29, 29',   // #7F1D1D
                    950 => '69, 10, 10',    // #450A0A
                ],
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LogUsageMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckPasswordChange::class,
                \App\Http\Middleware\CheckTenantActivation::class,
            ])
            ->databaseNotifications();
    }
}
