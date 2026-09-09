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
        FilamentAsset::register([
            Css::make('custom-css', asset('css/custom.css')),
        ]);

        return $panel
            ->id('tenant')
            ->plugin(
                ApiServicePlugin::make()
            )
            ->path('tenant')
            ->font('Space Grotesk')
            ->login()
            ->registration(\App\Filament\Tenant\Pages\Registration::class)
            ->passwordReset()
            ->authGuard('web')
            ->default()
            ->favicon(asset(path: 'images/favicon.png'))
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
                    50 => '230, 242, 250', // #E6F2FA
                    100 => '209, 229, 245', // #D1E5F5
                    200 => '173, 200, 239', // #ADC8EF
                    300 => '137, 171, 233', // #89ABE9
                    400 => '101, 144, 227', // #6590E3
                    500 => '65, 116, 221',  // #4174DD
                    600 => '53, 92, 178',   // #355CB2
                    700 => '41, 69, 135',   // #294587
                    800 => '29, 46, 92',    // #1D2E5C
                    900 => '17, 23, 49',    // #111731
                    950 => '8, 11, 24',     // #080B18
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
                    50 => '241, 239, 243',  // #F1EFF3
                    100 => '226, 223, 231', // #E2DFE7
                    200 => '196, 191, 209', // #C4BFD1
                    300 => '166, 159, 185', // #A69FB9
                    400 => '136, 127, 163', // #887FA3
                    500 => '106, 95, 141',  // #6A5F8D
                    600 => '86, 76, 114',   // #564C72
                    700 => '66, 57, 87',    // #423957
                    800 => '46, 38, 60',    // #2E263C
                    900 => '26, 19, 33',    // #1A1321
                    950 => '13, 9, 16',     // #0D0910
                ],
                'accent' => [
                    50 => '250, 235, 235',  // #FAEBEB
                    100 => '245, 215, 215', // #F5D7D7
                    200 => '235, 195, 195', // #EBC3C3
                    300 => '225, 174, 174', // #E1AEAE
                    400 => '215, 154, 154', // #D79A9A
                    500 => '205, 134, 134', // #CD8686
                    600 => '185, 114, 114', // #B97272
                    700 => '165, 94, 94',   // #A55E5E
                    800 => '145, 74, 74',   // #914A4A
                    900 => '125, 54, 54',   // #7D3636
                    950 => '63, 27, 27',    // #3F1B1B
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
