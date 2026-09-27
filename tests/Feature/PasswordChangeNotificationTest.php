<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TelegramUserTenant;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Services\PasswordChangeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Aviso de cambio de contraseña: mail (siempre) + Telegram (si el grupo tiene chat vigente).
 *
 * Es la parte que hace VISIBLE un cambio no autorizado: si alguien entra con una credencial filtrada
 * y la cambia, el dueño de la cuenta se entera por dos canales y con el método de recuperación a
 * mano.
 *
 * Sobre el envío por Telegram: el `BotsManager` del SDK está marcado `final`, así que el facade no
 * se puede mockear (ni interceptar) desde un test. Por eso se testea **a quién** se avisa
 * (`chatsDelGrupo`), que es la lógica propia; el envío en sí usa el mismo `Telegram::sendMessage(...)`
 * que ya usa el resto del repo (`SendDelayedProductNotification`) y con el `try/catch` que impide que
 * un bot caído tumbe el cambio de contraseña.
 */
class PasswordChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConTenant(): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function chatDelGrupo(User $user, string $expira): TelegramUserTenant
    {
        return TelegramUserTenant::create([
            'telegram_user_id' => 99887766,
            'tenant_id' => $user->tenant_id,
            'telegram_username' => 'cultivador',
            'expires_at' => $expira === 'nunca' ? null : now()->addDays((int) $expira),
        ]);
    }

    private function htmlDelMail(User $user, string $context = PasswordChangedNotification::CONTEXT_VOLUNTARY): string
    {
        return (new PasswordChangedNotification($context))->toMail($user)->render();
    }

    // ---------------------------------------------------------------- mail

    public function test_el_mail_siempre_trae_el_metodo_de_recuperacion(): void
    {
        config([
            'platform.brand_name' => null,
            'platform.admin_email' => null,
            'platform.site_url' => null,
            'platform.platform_url' => null,
        ]);

        $html = $this->htmlDelMail($this->usuarioConTenant());

        $this->assertStringContainsString('password-reset', $html, 'El mail tiene que decir cómo recuperar la cuenta.');
    }

    public function test_el_mail_trae_el_contacto_del_servicio_cuando_esta_configurado(): void
    {
        config([
            'platform.brand_name' => 'Mi Plataforma',
            'platform.admin_email' => 'soporte@ejemplo.test',
            'platform.site_url' => 'https://ejemplo.test',
        ]);

        $html = $this->htmlDelMail($this->usuarioConTenant());

        $this->assertStringContainsString('soporte@ejemplo.test', $html);
        $this->assertStringContainsString('https://ejemplo.test', $html);
        $this->assertStringContainsString('Contacto del servicio', $html);
    }

    public function test_sin_configuracion_no_inventa_contacto(): void
    {
        config([
            'platform.brand_name' => null,
            'platform.admin_email' => null,
            'platform.site_url' => null,
            'platform.platform_url' => null,
        ]);

        $html = $this->htmlDelMail($this->usuarioConTenant());

        $this->assertStringNotContainsString(
            'Contacto del servicio',
            $html,
            'Sin datos configurados el mail no puede mostrar un contacto de otra instalación.'
        );
    }

    public function test_el_aviso_obligatorio_dice_que_lo_pidio_la_plataforma(): void
    {
        config(['platform.brand_name' => null]);

        $html = $this->htmlDelMail($this->usuarioConTenant(), PasswordChangedNotification::CONTEXT_FORCED);

        $this->assertStringContainsString('obligatorio', $html);
    }

    // ------------------------------------------------------------ telegram

    public function test_avisa_por_telegram_al_chat_vigente_del_grupo(): void
    {
        Notification::fake();

        $user = $this->usuarioConTenant();
        $this->chatDelGrupo($user, 'nunca');

        $chats = app(PasswordChangeNotifier::class)->chatsDelGrupo($user);

        $this->assertCount(1, $chats, 'El chat vigente del grupo tiene que recibir el aviso.');
        $this->assertSame(99887766, (int) $chats->first()->telegram_user_id);

        app(PasswordChangeNotifier::class)->notify($user, PasswordChangedNotification::CONTEXT_VOLUNTARY);

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_no_avisa_por_telegram_si_el_grupo_no_tiene_chat_asociado(): void
    {
        Notification::fake();

        $user = $this->usuarioConTenant();

        $this->assertCount(0, app(PasswordChangeNotifier::class)->chatsDelGrupo($user));

        app(PasswordChangeNotifier::class)->notify($user, PasswordChangedNotification::CONTEXT_VOLUNTARY);

        // El mail sí sale igual: no tener Telegram no puede dejar al usuario sin aviso.
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_no_avisa_a_un_chat_vencido(): void
    {
        Notification::fake();

        $user = $this->usuarioConTenant();
        $this->chatDelGrupo($user, '-1');

        $this->assertCount(
            0,
            app(PasswordChangeNotifier::class)->chatsDelGrupo($user),
            'Un chat vencido no es un canal válido: no se le avisa.'
        );
    }
}
