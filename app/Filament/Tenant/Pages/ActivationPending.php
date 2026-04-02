<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ActivationPending extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.tenant.pages.activation-pending';

    protected static ?string $title = 'Activación Pendiente';

    protected static ?string $navigationLabel = 'Activación Pendiente';

    protected static ?string $slug = 'activation-pending';

    protected static bool $shouldRegisterNavigation = false;

    protected function hasFullWidthLayout(): bool
    {
        return true;
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }

    public function mount(): void
    {
        // Verificar que el usuario tenga un tenant inactivo
        $user = Auth::user();
        if (!$user || !$user->tenant_id) {
            // Si no hay usuario o no tiene tenant, redirigir al login
            redirect()->route('filament.tenant.auth.login');
            return;
        }
        
        if ($user->tenant?->active) {
            // Redirigir al dashboard si el tenant está activo
            redirect()->route('filament.tenant.pages.dashboard');
            return;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                                                
                        Placeholder::make('message')
                            ->label('')
                            ->content('Tu cuenta está siendo verificada. Te notificaremos por correo electrónico cuando esté lista, sumate al servidor de discord si querés interactuar o realizar alguna consulta.')
                            ->extraAttributes(['class' => 'text-gray-600 text-center']),
                    ])
                    ->columns(1),
                
                Actions::make([
                    Action::make('logout')
                        ->label('Cerrar Sesión')
                        ->color('gray')
                        ->action(function () {
                            Auth::logout();
                            return redirect()->route('filament.tenant.auth.login');
                        }),
                ])->alignCenter(),
            ]);
    }
}