<?php

namespace App\Filament\Tenant\Pages;

use Filament\Actions as PageActions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * "Mi cuenta" — perfil del usuario (T10.1).
 *
 * Antes de esto no había dónde editar el nombre ni el email: el usuario quedaba con lo que le puso
 * el seeder o el owner al invitarlo, y no había forma de corregirlo desde la plataforma.
 *
 * Qué NO está acá a propósito:
 *  - **Contraseña**: tiene su propia página (`CambiarPassword`) porque su trazabilidad es de
 *    seguridad y va separada de la telemetría de uso (corrección de diseño de Frankie, 2026-09-27).
 *  - **Tokens de API**: son del grupo de trabajo, no de la persona. Siguen en "Mi Grupo".
 */
class Cuenta extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.tenant.pages.cuenta';

    protected static ?string $navigationLabel = 'Mi cuenta';

    protected static ?string $title = 'Mi cuenta';

    protected static ?string $navigationGroup = 'Grupo de trabajo';

    protected static ?int $navigationSort = 998;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Perfil')
                    ->description('Tu nombre y el email con el que entrás al sistema.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                // El email es la credencial de login: tiene que quedar único.
                                // Se ignora el propio usuario (si no, guardar sin tocarlo fallaría).
                                Rule::unique('users', 'email')->ignore(Auth::id()),
                            ]),
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

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        Notification::make()
            ->title('Datos actualizados')
            ->body('Tu nombre y tu email quedaron guardados.')
            ->success()
            ->send();
    }

    /**
     * @return array<int, PageActions\Action>
     */
    protected function getFormActions(): array
    {
        return [
            PageActions\Action::make('guardar')
                ->label('Guardar cambios')
                ->action('guardar'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
