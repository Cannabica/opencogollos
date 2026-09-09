<?php

namespace Tests\Feature;

use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Seed;
use App\Models\Plant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditActionPlantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_shows_existing_plants_selected(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $indoor = Indoor::create([
            'name' => 'Carpa Test', 'tenant_id' => $tenant->id,
            'large' => 80, 'width' => 80, 'height' => 160,
        ]);
        $seedId = Seed::create([
            'name' => 'Test Auto', 'seed_type' => 'auto', 'flowering_time' => 9.5,
            'ratio_thc' => 20, 'ratio_cbd' => 0,
        ])->id;
        $p1 = Plant::create(['name' => 'Planta Uno', 'seed_id' => $seedId, 'indoor_id' => $indoor->id, 'state' => 'Etapa Vegetativa', 'flowerpot' => 'M', 'capacity' => 10]);
        $p2 = Plant::create(['name' => 'Planta Dos', 'seed_id' => $seedId, 'indoor_id' => $indoor->id, 'state' => 'Etapa Vegetativa', 'flowerpot' => 'M', 'capacity' => 10]);
        $type = ActionType::create(['name' => 'Registrar Riego', 'action_class' => \App\Utilities\PlantActions\RegisterIrrigation::class]);
        $action = \App\Models\Action::create([
            'tenant_id' => $tenant->id,
            'indoor_id' => $indoor->id,
            'action_type_id' => $type->id,
            'action_date' => now(),
            'data' => ['irrigation' => ['irrigation_type' => 'liters', 'liters' => 1.0]],
        ]);
        $action->plants()->sync([$p1->id, $p2->id]);

        $resp = $this->actingAs($user)->get("/tenant/actions/{$action->id}/edit");
        if ($resp->status() !== 200) {
            $body = $resp->getContent();
            if (preg_match('/<title>(.*?)<\/title>/s', $body, $tm)) fwrite(STDERR, "TITLE: " . $tm[1] . "\n");
            if (preg_match('/(Undefined|Class [A-Za-z\\\\]+|Call to a member)[^<]{0,140}/', $body, $mm)) fwrite(STDERR, "ERR: " . strip_tags($mm[0]) . "\n");
            $resp->assertOk();
        }
        $html = $resp->getContent();

        $checks = [];
        preg_match_all('/<input[^>]*type="checkbox"[^>]*value="(\d+)"[^>]*>/', $html, $m);
        foreach ($m[1] as $id) {
            $checks[(int) $id] = false;
        }
        preg_match_all('/<input[^>]*type="checkbox"[^>]*checked[^>]*value="(\d+)"[^>]*>|<input[^>]*type="checkbox"[^>]*value="(\d+)"[^>]*checked[^>]*>/', $html, $m2);
        foreach (array_filter(array_merge($m2[1], $m2[2])) as $id) {
            $checks[(int) $id] = true;
        }

        $this->assertArrayHasKey($p1->id, $checks, 'checkbox planta 1 no renderizado');
        $this->assertTrue($checks[$p1->id] ?? false, 'planta 1 deberia venir marcada en el edit');
        $this->assertTrue($checks[$p2->id] ?? false, 'planta 2 deberia venir marcada en el edit');
    }
}
