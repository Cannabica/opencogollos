<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions as PageActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;

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
                            ->rules([
                                Password::min(8)
                                    ->letters()
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                            ])
                            ->confirmed()
                            ->helperText('La contraseña debe tener al menos 8 caracteres, incluir letras mayúsculas y minúsculas, números y símbolos.'),
                        
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

        // Show success notification
        Notification::make()
            ->title('Contraseña Cambiada Exitosamente')
            ->body('Tu contraseña ha sido actualizada correctamente. Ahora puedes acceder al sistema.')
            ->success()
            ->send();

        // Redirect to dashboard
        redirect()->route('filament.tenant.pages.dashboard');
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