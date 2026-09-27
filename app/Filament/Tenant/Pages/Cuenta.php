<?php

namespace App\Filament\Tenant\Pages;

use App\Models\EmailChangeRequest;
use App\Notifications\EmailChangeConfirmationNotification;
use App\Notifications\EmailChangeRequestedNotification;
use Filament\Actions as PageActions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
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

    // Se llega desde "Mi Grupo" (bloque de datos del grupo), no desde el menú lateral: la experiencia
    // de cuenta vive integrada ahí (decisión de Frankie, revisión del 2026-09-27).
    protected static bool $shouldRegisterNavigation = false;

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
                            // Para el OWNER el email es el mismo que el del grupo y es lo que lo identifica
                            // como owner: si lo cambia acá, pierde el panel (medido 2026-09-27). Se muestra
                            // de sólo lectura y el guardado también lo rechaza (ver `guardar()`).
                            ->disabled(fn () => Auth::user()->isTenantOwner())
                            // ⚠️ `dehydrated` sólo se apaga para el OWNER: si se apagara para todos, el
                            // email no llegaría a los datos del form y el doble opt-in de los miembros
                            // nunca se dispararía (roto, detectado por los tests 2026-09-27).
                            ->dehydrated(fn () => ! Auth::user()->isTenantOwner())
                            ->helperText(fn () => Auth::user()->isTenantOwner()
                                ? 'Tu email es el del grupo: es lo que te identifica como owner.'
                                : null)
                            ->rules([
                                // El email es la credencial de login: tiene que quedar único.
                                // Se ignora el propio usuario (si no, guardar sin tocarlo fallaría).
                                Rule::unique('users', 'email')->ignore(Auth::id()),
                            ])
                            // `live()` porque la sección de confirmación de abajo aparece sólo cuando el
                            // email efectivamente cambió.
                            ->live(),
                    ])
                    ->columns(1),

                // La contraseña se pide AL FINAL y sólo si hay un cambio que guardar (revisión de
                // Frankie, 2026-09-27): quien viene a corregir su nombre no ve un campo de contraseña.
                Section::make('Confirmá el cambio de email')
                    ->description('El email es tu credencial de acceso, así que este cambio se confirma desde la dirección nueva.')
                    ->visible(fn ($get) => $get('email') !== Auth::user()->email)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Contraseña actual')
                            ->password()
                            ->required()
                            ->rule(function () {
                                return function ($attribute, $value, $fail) {
                                    if (filled($value) && ! Hash::check($value, Auth::user()->password)) {
                                        $fail('Para cambiar el email necesitás tu contraseña actual.');
                                    }
                                };
                            })
                            ->helperText('Te vamos a mandar un link a la dirección nueva: hasta que lo confirmes, seguís entrando con la actual.'),
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

        $emailActual = $user->email;
        $emailNuevo = $data['email'] ?? $emailActual;

        // Red de seguridad del bloqueo del owner (el campo ya viene deshabilitado, pero el guard va acá
        // también): para el owner el email es el del grupo y cambiarlo le sacaría el panel.
        if ($user->isTenantOwner() && $emailNuevo !== $emailActual) {
            $this->addError(
                'email',
                'Tu email es el del grupo y es lo que te identifica como owner: no se cambia desde acá. Escribinos si necesitás cambiarlo.'
            );

            return;
        }

        $user->name = $data['name'];
        $user->save();

        // El nombre se guarda siempre; el email NO: es la credencial de login, así que va por doble
        // opt-in (se confirma desde la dirección nueva, ver EmailChangeController).
        if ($emailNuevo === $emailActual) {
            Notification::make()
                ->title('Datos actualizados')
                ->body('Tu nombre quedó guardado.')
                ->success()
                ->send();

            return;
        }

        ['token' => $token] = EmailChangeRequest::createFor($user, $emailNuevo);

        $linkEnviado = false;

        try {
            // Link a la dirección NUEVA (la mitad que falta del opt-in) y aviso a la VIEJA, para que
            // se entere en la casilla que sí controla. El nombre va explícito: con `route('mail')`
            // el destinatario es un AnonymousNotifiable y NO tiene `->name` (bug medido 2026-09-27:
            // la excepción la tapaba el catch y el usuario veía "te mandamos un link" sin mail).
            NotificationFacade::route('mail', $emailNuevo)
                ->notify(new EmailChangeConfirmationNotification($emailNuevo, $token, $user->name));

            NotificationFacade::route('mail', $emailActual)
                ->notify(new EmailChangeRequestedNotification($emailActual, $emailNuevo, $user->name));

            $linkEnviado = true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la confirmación del cambio de email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Si el mail no salió, no se puede decir "revisá tu dirección": el cambio queda pendiente y
        // el usuario tiene que saber que el link no está en camino.
        if ($linkEnviado) {
            Notification::make()
                ->title('Revisá tu nueva dirección')
                ->body('Te mandamos un link a ' . $emailNuevo . ' para confirmar el cambio. Hasta que lo confirmes seguís entrando con ' . $emailActual . '.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No pudimos enviar el mail de confirmación')
                ->body('El cambio no se aplicó y seguís entrando con ' . $emailActual . '. Reintentá en unos minutos.')
                ->danger()
                ->send();
        }
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
