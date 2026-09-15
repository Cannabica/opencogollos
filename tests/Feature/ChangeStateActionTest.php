<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\Actions\Services\ActionRecordService;
use App\Filament\Tenant\Resources\ActionsResource;
use App\Filament\Tenant\Resources\ActionsResource\Pages\CreateActions;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use App\Utilities\PlantActions\RegisterState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regresión: registrar una acción de "Cambio de Estado" (plántula -> vegetativo)
 * debe dejar la planta en el estado nuevo.
 *
 * Bug histórico (corregido): RegisterState::trigger() leía $data['state'] pero el
 * panel persiste data anidado (data.change_state.state) -> "Undefined array key state",
 * la acción se guardaba y la planta quedaba con el estado viejo.
 */
class ChangeStateActionTest extends TestCase
{
    use RefreshDatabase;

    private function entorno(): array
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
        $plant = Plant::create([
            'name' => 'Planta Test', 'seed_id' => $seedId, 'indoor_id' => $indoor->id,
            'state' => 'Etapa de Plantula', 'flowerpot' => 'M', 'capacity' => 10,
        ]);

        // El form y el trigger del panel indexan el tipo de acción por id 7.
        $type = new ActionType();
        $type->id = 7;
        $type->name = 'Registrar Cambio de Estado';
        $type->action_class = RegisterState::class;
        $type->save();

        return compact('tenant', 'user', 'indoor', 'plant', 'type');
    }

    /** Forma exacta del data que arma el form del panel (field data.change_state.state + statePath data). */
    private function dataDelForm(array $env): array
    {
        return [
            'action_type_id' => $env['type']->id,
            'action_date' => now()->toDateString(),
            'indoor_id' => $env['indoor']->id,
            'plants' => [$env['plant']->id],
            'data' => ['change_state' => ['state' => 'Etapa Vegetativa']],
        ];
    }

    public function test_registrar_cambio_de_estado_pasa_la_planta_a_vegetativo(): void
    {
        $env = $this->entorno();
        $this->actingAs($env['user']);

        $action = (new ActionRecordService())->handleRecordCreation($this->dataDelForm($env));

        $this->assertSame(1, $action->plants()->count(), 'la accion debe quedar asociada a la planta');
        $this->assertSame(['change_state' => ['state' => 'Etapa Vegetativa']], $action->fresh()->data);

        ActionsResource::executeActionTrigger($action->fresh());

        $this->assertSame('Etapa Vegetativa', $env['plant']->fresh()->state);
    }

    public function test_flujo_real_del_panel_deja_la_planta_en_el_estado_nuevo(): void
    {
        $env = $this->entorno();
        $this->actingAs($env['user']);

        Livewire::test(CreateActions::class)
            ->fillForm([
                'action_type_id' => $env['type']->id,
                'action_date' => now()->toDateString(),
                'indoor_id' => $env['indoor']->id,
                'plants' => [$env['plant']->id],
                'data.change_state.state' => 'Etapa Vegetativa',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $action = Action::withoutGlobalScopes()->latest('id')->first();

        $this->assertNotNull($action, 'la accion debe haberse guardado');
        $this->assertSame(7, (int) $action->action_type_id);
        $this->assertSame(['change_state' => ['state' => 'Etapa Vegetativa']], $action->data);
        $this->assertSame('Etapa Vegetativa', $env['plant']->fresh()->state);
    }

    public function test_trigger_tolera_data_plano_y_data_vacio(): void
    {
        $env = $this->entorno();
        $this->actingAs($env['user']);

        $trigger = new RegisterState();

        // forma plana (getConstructorArguments)
        $trigger->trigger($env['plant'], ['state' => 'Etapa Vegetativa']);
        $this->assertSame('Etapa Vegetativa', $env['plant']->fresh()->state);

        // sin estado no debe romper ni pisar el estado actual
        $trigger->trigger($env['plant'], []);
        $this->assertSame('Etapa Vegetativa', $env['plant']->fresh()->state);
    }
}
