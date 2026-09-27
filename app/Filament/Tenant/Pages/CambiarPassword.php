<?php

namespace App\Filament\Tenant\Pages;

use App\Models\SecurityEvent;
use Filament\Actions as PageActions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * "Cambiar contraseña" — página propia (corrección de diseño de Frankie, 2026-09-27).
 *
 * Por qué va separada de "Mi cuenta" y no como una sección adentro: el cambio de contraseña tiene
 * **su propia trazabilidad** (`security_events`, aparte de la telemetría de uso de `usage_events`),
 * se audita aparte y el "cuándo cambió la contraseña cada uno" es una pregunta de seguridad, no de
 * uso del producto. Tenerlo en su página mantiene esa frontera también en la UI.
 *
 * Convive con `PasswordChange` (el cambio OBLIGATORIO del primer acceso, fuera del menú y forzado
 * por `CheckPasswordChange`): los dos registran el mismo evento, con distinto `context` —
 * `voluntary` desde acá, `forced` desde allá.
 */
class CambiarPassword extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.tenant.pages.cambiar-password';

    protected static ?string $navigationLabel = 'Cambiar contraseña';

    protected static ?string $title = 'Cambiar contraseña';

    protected static ?string $navigationGroup = 'Grupo de trabajo';

    protected static ?int $navigationSort = 999;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Contraseña')
                    ->description('Elegí una contraseña nueva. Queda registrado en la trazabilidad de seguridad de tu cuenta.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Contraseña actual')
                            ->password()
                            ->required()
                            ->rule(function () {
                                return function ($attribute, $value, $fail) {
                                    if (! Hash::check($value, Auth::user()->password)) {
                                        $fail('La contraseña actual no es correcta.');
                                    }
                                };
                            }),

                        TextInput::make('new_password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->required()
                            ->rules([
                                Password::min(8)
                                    ->letters()
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols(),
                            ])
                            ->confirmed()
                            ->helperText('Al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.'),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function guardar(): void
    {
        $data = $this->form->getState();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->password = Hash::make($data['new_password']);
        // Si además venía de un cambio obligatorio, deja de estar pendiente.
        $user->force_password_change = false;
        $user->save();

        // Trazabilidad de seguridad (tabla propia, separada de la telemetría de uso).
        SecurityEvent::record($user, SecurityEvent::PASSWORD_CHANGED, SecurityEvent::CONTEXT_VOLUNTARY);

        Notification::make()
            ->title('Contraseña actualizada')
            ->body('Tu contraseña quedó guardada.')
            ->success()
            ->send();

        $this->form->fill();
    }

    /**
     * @return array<int, PageActions\Action>
     */
    protected function getFormActions(): array
    {
        return [
            PageActions\Action::make('guardar')
                ->label('Cambiar contraseña')
                ->action('guardar'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
