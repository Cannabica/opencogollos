<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El caso "no autentica con password inválida" se movió a
 * `TenantLoginCredentialsTest`, porque acá se lo probaba contra `POST /login`
 * (ruta del scaffold apagada): el test pasaba sin probar nada. Queda sólo el
 * logout, que sí pega a una ruta viva.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
