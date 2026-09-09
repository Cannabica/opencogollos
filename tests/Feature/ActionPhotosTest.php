<?php

namespace Tests\Feature;

use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActionPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_list_shows_photo_thumbnails_when_files_exist(): void
    {
        // accion de observacion con foto cuyo archivo existe en disco
        Storage::fake('public');
        Storage::disk('public')->put('observations/planta.jpg', 'foto');

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $indoor = Indoor::create([
            'name' => 'Carpa Test',
            'tenant_id' => $tenant->id,
            'large' => 80, 'width' => 80, 'height' => 160,
        ]);
        $plant = Plant::create([
            'name' => 'Planta Test',
            'seed_id' => \App\Models\Seed::create([
                'name' => 'Test Auto', 'seed_type' => 'auto', 'flowering_time' => 9.5,
                'ratio_thc' => 20, 'ratio_cbd' => 0,
            ])->id,
            'indoor_id' => $indoor->id,
            'state' => 'Etapa Vegetativa',
            'flowerpot' => 'Maceta', 'capacity' => 10,
        ]);
        $type = ActionType::create(['name' => 'Registrar Observación con foto', 'action_class' => 'x']);
        $action = \App\Models\Action::create([
            'tenant_id' => $tenant->id,
            'indoor_id' => $indoor->id,
            'action_type_id' => $type->id,
            'action_date' => now(),
            'data' => ['observation' => ['comments' => 'con foto', 'image' => ['observations/planta.jpg']]],
        ]);
        $action->plants()->sync([$plant->id]);

        $html = $this->actingAs($user)->get('/tenant/actions')->assertOk()->getContent();
        $this->assertStringContainsString('/storage/observations/planta.jpg', $html);
    }

    public function test_actions_list_does_not_break_without_photos(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $indoor = Indoor::create([
            'name' => 'Carpa Test',
            'tenant_id' => $tenant->id,
            'large' => 80, 'width' => 80, 'height' => 160,
        ]);
        $type = ActionType::create(['name' => 'Registrar Riego', 'action_class' => 'x']);
        \App\Models\Action::create([
            'tenant_id' => $tenant->id,
            'indoor_id' => $indoor->id,
            'action_type_id' => $type->id,
            'action_date' => now(),
            'data' => ['irrigation' => ['irrigation_type' => 'manual', 'liters' => 0.5, 'amount' => 0.5]],
        ]);

        $this->actingAs($user)->get('/tenant/actions')->assertOk();
    }
}
