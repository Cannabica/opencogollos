<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginRouteTest extends TestCase
{
    public function test_guests_hitting_a_protected_route_redirect_to_the_tenant_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/tenant/login');
    }

    public function test_named_login_route_redirects_to_the_tenant_panel(): void
    {
        $this->get(route('login'))->assertRedirect('/tenant/login');
    }
}
