<?php

namespace App\Filament\Tenant\Pages;

use App\Models\SecurityEvent;
use App\Notifications\PasswordChangedNotification;
use App\Services\PasswordChangeNotifier;
use App\Support\PasswordRequirements;
use Filament\Actions as PageActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordChange extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.tenant.pages.password-change';

    protected static ?string $title = 'Cambio de Contraseña Obligatorio';

    protected static ?string $navigationLabel = 'Cambio de Contraseña';

    protected static ?string $slug = 'password-change';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

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
        // Verify that the user needs to change their password
        $user = Auth::user();
        if (!$user || !$user->force_password_change) {
            // If no user or password change not required, redirect to dashboard
            redirect()->route('filament.tenant.pages.dashboard');
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cambio de Contraseña Obligatorio')
                    ->description('Por razones de seguridad, debes cambiar tu contraseña antes de continuar.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Contraseña Actual')
                            ->password()
                            ->required()
                            ->rule(function () {
                                return function ($attribute, $value, $fail) {
                                    if (!Hash::check($value, Auth::user()->password)) {
                                        $fail('La contraseña actual no es correcta.');
                                    }
                                };
                            }),
                        
                        TextInput::make('new_password')
                            ->label('Nueva Contraseña')
                            ->password()
                            ->required()
                            ->rules(PasswordRequirements::rule())
                            ->confirmed()
                            // `live()` para que los requisitos se marquen mientras se tipea.
                            ->live()
                            ->helperText('Los requisitos se marcan abajo a medida que escribís.'),

                        // Validación visual en vivo (revisión de Frankie, 2026-09-27): mismo desglose que
                        // la regla, porque sale de la misma clase (PasswordRequirements).
                        Placeholder::make('requisitos_password')
                            ->hiddenLabel()
                            ->content(fn ($get) => view('filament.tenant.partials.requisitos-password', [
                                'password' => $get('new_password'),
                            ])),
                        
                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar Nueva Contraseña')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function changePassword(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Update the user's password
        $user->password = Hash::make($data['new_password']);
        $user->force_password_change = false;
        $user->save();

        // Trazabilidad de seguridad (tabla propia, separada de la telemetría de uso): el mismo evento
        // que el cambio voluntario, con el contexto que distingue que acá lo impuso el sistema.
        SecurityEvent::record($user, SecurityEvent::PASSWORD_CHANGED, SecurityEvent::CONTEXT_FORCED);

        // Aviso a la cuenta: mail siempre, y Telegram si el grupo tiene un chat asociado.
        app(PasswordChangeNotifier::class)->notify($user, PasswordChangedNotification::CONTEXT_FORCED);

        // Se cierra la sesión: la credencial vieja ya no vale, así que hay que volver a entrar con la nueva.
        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        Notification::make()
            ->title('Contraseña cambiada')
            ->body('Entrá de nuevo con tu contraseña nueva.')
            ->success()
            ->send();

        redirect()->route('filament.tenant.auth.login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirectRoute('filament.tenant.auth.login');
    }

    protected function getFormActions(): array
    {
        return [
            PageActions\Action::make('change_password')
                ->label('Cambiar Contraseña')
                ->action('changePassword'),
            
            PageActions\Action::make('logout')
                ->label('Cerrar Sesión')
                ->color('gray')
                ->action('logout')
                ->requiresConfirmation()
                ->modalHeading('Cerrar Sesión')
                ->modalDescription('¿Estás seguro de que deseas cerrar sesión?')
                ->modalSubmitActionLabel('Sí, cerrar sesión'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}