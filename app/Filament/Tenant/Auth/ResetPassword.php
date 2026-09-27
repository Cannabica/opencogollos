<?php

namespace App\Filament\Tenant\Auth;

use App\Support\PasswordRequirements;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\PasswordResetResponse;
use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;

/**
 * Recuperación de contraseña (el link "olvidé mi contraseña") con la validación visual en vivo.
 *
 * Extiende la página de Filament porque es la única forma de meterle el desglose de requisitos: el
 * esquema del formulario lo arma el vendor. Lo que cambia respecto del original:
 *
 *  - la regla sale de `PasswordRequirements` (la misma clase que dibuja el desglose: no hay dos
 *    verdades sobre qué pide la contraseña),
 *  - el campo se marca `live()` y se agrega el componente con los requisitos, así se van tildando
 *    mientras se tipea.
 *
 * Se registra en `TenantPanelProvider` con `->passwordReset(ResetPassword::class)`.
 */
class ResetPassword extends BaseResetPassword
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),

            // Mismo partial que usan "Cambiar contraseña" y el cambio obligatorio del primer acceso.
            Placeholder::make('requisitos_password')
                ->hiddenLabel()
                ->content(fn ($get) => view('filament.tenant.partials.requisitos-password', [
                    'password' => $get('password'),
                ])),

            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Nueva contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rules(PasswordRequirements::rule())
            ->same('passwordConfirmation')
            // `live(onBlur: true)`: los requisitos se actualizan al salir del campo (con `live()` a secas,
            // el `same()` da "no coincide" mientras se escribe la confirmación).
            ->live(onBlur: true);
    }

    /**
     * Definir la contraseña desde acá **termina** el primer acceso si venía de una invitación.
     *
     * El alta de una persona deja `force_password_change = true` para que el sistema le pida cambiarla.
     * Pero cuando la persona llega por el link de la invitación ("elegí tu contraseña") ya la está
     * definiendo: si el flag queda en true, el middleware la manda a cambiarla otra vez al entrar
     * (loop reportado por Frankie, 2026-09-27). Acá se limpia y queda registrado como el
     * establecimiento inicial.
     */
    public function resetPassword(): ?PasswordResetResponse
    {
        // El cierre del primer acceso NO se hace acá: lo hace el listener
        // `LimpiarCambioObligatorioAlDefinirLaContrasena`, escuchando el evento `PasswordReset`, porque
        // esa ruta puede quedar atendida por la clase de Filament (ver TenantPanelProvider) y este
        // override nunca correría.
        return parent::resetPassword();
    }
}
