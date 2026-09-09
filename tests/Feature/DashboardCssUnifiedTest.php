<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCssUnifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_vite_css_not_legacy(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $html = $this->actingAs($user)->get('/tenant')->assertOk()->getContent();
        foreach (explode("\n", $html) as $line) {
            if (str_contains($line, 'custom.css')) {
                fwrite(STDERR, "LÍNEA CON custom.css: " . trim($line) . "\n");
            }
        }
        $this->assertStringContainsString('dashboard-', $html);
        $this->assertStringNotContainsString('css/custom.css', $html);
        foreach (explode("\n", $html) as $line) {
            if (str_contains($line, 'font') && (str_contains($line, 'googleapis') || str_contains($line, 'fonts.') || str_contains($line, 'Inter') || str_contains($line, 'Grotesk'))) {
                fwrite(STDERR, "FONT LINE: " . trim($line) . "\n");
            }
        }
        $this->assertStringContainsString("family=inter", $html); // tipografía de marca (vía fonts.bunny.net)
        $this->assertStringNotContainsString('Space+Grotesk', $html);
    }
}
