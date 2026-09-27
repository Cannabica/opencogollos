<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\Actions\Services\ActionRecordService;
use App\Filament\Tenant\Resources\ActionsResource;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use App\Utilities\PlantActions\RegisterTransplant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión: el trigger de transplante debe aplicar los datos de SU acción
 * (data.transplant.*), no los de la "última acción de la planta".
 *
 * Bug histórico: trigger() ignoraba $data y hacía $plant->actions()->latest()->first();
 * al editar un transplante cuando la planta ya tenía otra acción posterior, tomaba esa
 * otra acción -> InvalidArgumentException "Datos de transplante incompletos".
 */
class TransplantTriggerTest extends TestCase
{
    use RefreshDatabase;

    private function entorno(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        $indoor = Indoor::create(['name' => 'Carpa T', 'tenant_id' => $tenant->id, 'large' => 80, 'width' => 80, 'height' => 160]);
        $seedId = Seed::create(['name' => 'S T', 'seed_type' => 'auto', 'flowering_time' => 9.5, 'ratio_thc' => 20, 'ratio_cbd' => 0])->id;
        $plant = Plant::create([
            'name' => 'P T', 'seed_id' => $seedId, 'indoor_id' => $indoor->id,
            'state' => 'Etapa Vegetativa', 'flowerpot' => 'Geotextiles', 'capacity' => 10,
        ]);

        $trans = new ActionType();
        $trans->id = 4;
        $trans->name = 'Registrar Transplante';
        $trans->action_class = RegisterTransplant::class;
        $trans->save();

        $riego = new ActionType();
        $riego->id = 1;
        $riego->name = 'Registrar Riego';
        $riego->action_class = \App\Utilities\PlantActions\RegisterIrrigation::class;
        $riego->save();

        return compact('tenant', 'user', 'indoor', 'plant');
    }

    public function test_crear_transplante_aplica_los_datos(): void
    {
        $env = $this->entorno();

        $action = (new ActionRecordService())->handleRecordCreation([
            'action_type_id' => 4,
            'action_date' => now()->toDateString(),
            'indoor_id' => $env['indoor']->id,
            'plants' => [$env['plant']->id],
            'data' => ['transplant' => ['new_flowerpot' => 'Plásticas', 'new_capacity' => 20]],
        ]);

        ActionsResource::executeActionTrigger($action->fresh());

        $this->assertSame('Plásticas', $env['plant']->fresh()->flowerpot);
        $this->assertSame(20.0, (float) $env['plant']->fresh()->capacity);
    }

    public function test_editar_transplante_con_accion_posterior_no_explota_y_aplica(): void
    {
        $env = $this->entorno();

        $transplante = (new ActionRecordService())->handleRecordCreation([
            'action_type_id' => 4,
            'action_date' => now()->toDateString(),
            'indoor_id' => $env['indoor']->id,
            'plants' => [$env['plant']->id],
            'data' => ['transplant' => ['new_flowerpot' => 'Plásticas', 'new_capacity' => 20]],
        ]);

        // Después del transplante llega otra acción (riego) -> before: latest() era el riego
        sleep(1);
        (new ActionRecordService())->handleRecordCreation([
            'action_type_id' => 1,
            'action_date' => now()->toDateString(),
            'indoor_id' => $env['indoor']->id,
            'plants' => [$env['plant']->id],
            'data' => ['irrigation' => ['irrigation_type' => 'liters', 'liters' => 1]],
        ]);

        // El usuario edita el transplante y guarda: la planta vuelve a su estado previo
        $env['plant']->update(['flowerpot' => 'Geotextiles', 'capacity' => 10]);

        ActionsResource::executeActionTrigger($transplante->fresh());

        $this->assertSame('Plásticas', $env['plant']->fresh()->flowerpot);
        $this->assertSame(20.0, (float) $env['plant']->fresh()->capacity);
    }

    public function test_fallback_sin_data_usa_la_ultima_accion(): void
    {
        $env = $this->entorno();

        (new ActionRecordService())->handleRecordCreation([
            'action_type_id' => 4,
            'action_date' => now()->toDateString(),
            'indoor_id' => $env['indoor']->id,
            'plants' => [$env['plant']->id],
            'data' => ['transplant' => ['new_flowerpot' => 'Bolsones', 'new_capacity' => 30]],
        ]);

        // Callers viejos que no pasan data siguen funcionando (fallback)
        (new RegisterTransplant())->trigger($env['plant'], null);

        $this->assertSame('Bolsones', $env['plant']->fresh()->flowerpot);
        $this->assertSame(30.0, (float) $env['plant']->fresh()->capacity);
    }
}
