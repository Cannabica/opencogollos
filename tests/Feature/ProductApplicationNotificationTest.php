<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\ActionsResource;
use App\Jobs\SendDelayedProductNotification;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class ProductApplicationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestEnvironment()
    {
        // Use auto-incrementing IDs for safety but capture them
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@tenant.com',
        ]);

        $actionType = new ActionType();
        $actionType->id = 3;
        $actionType->name = 'Registrar Aplique producto';
        $actionType->action_class = 'App\\Utilities\\PlantActions\\RegisterApplication';
        $actionType->save();

        $indoor = Indoor::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Indoor',
            'large' => 100,
            'width' => 100,
            'height' => 100,
        ]);

        return ['tenant' => $tenant, 'actionType' => $actionType, 'indoor' => $indoor];
    }

    public function test_it_dispatches_notification_for_immediate_reminder_5s()
    {
        Queue::fake();
        $env = $this->createTestEnvironment();

        $action = Action::create([
            'tenant_id' => $env['tenant']->id,
            'indoor_id' => $env['indoor']->id,
            'action_type_id' => $env['actionType']->id,
            'action_date' => now(),
            'data' => [
                'product_application' => [
                    'application_type' => 'flora',
                    'reminder_time' => '5s',
                ]
            ]
        ]);

        ActionsResource::executeActionTrigger($action);

        Queue::assertPushed(SendDelayedProductNotification::class, function ($job) use ($env) {
            return $job->applicationType === 'flora' && $job->tenantId === $env['tenant']->id;
        });
    }

    public function test_it_dispatches_notification_for_delayed_reminder_7d()
    {
        Queue::fake();
        Carbon::setTestNow(now());
        $env = $this->createTestEnvironment();

        $action = Action::create([
            'tenant_id' => $env['tenant']->id,
            'indoor_id' => $env['indoor']->id,
            'action_type_id' => $env['actionType']->id,
            'action_date' => now(),
            'data' => [
                'product_application' => [
                    'application_type' => 'vege',
                    'reminder_time' => '7d',
                ]
            ]
        ]);

        ActionsResource::executeActionTrigger($action);

        Queue::assertPushed(SendDelayedProductNotification::class, function ($job) {
            return $job->applicationType === 'vege' &&
                $job->delay->isSameDay(now()->addDays(7));
        });
    }

    public function test_it_dispatches_notification_for_delayed_reminder_1d()
    {
        Queue::fake();
        Carbon::setTestNow(now());
        $env = $this->createTestEnvironment();

        $action = Action::create([
            'tenant_id' => $env['tenant']->id,
            'indoor_id' => $env['indoor']->id,
            'action_type_id' => $env['actionType']->id,
            'action_date' => now(),
            'data' => [
                'product_application' => [
                    'application_type' => 'plantula',
                    'reminder_time' => '1d',
                ]
            ]
        ]);

        ActionsResource::executeActionTrigger($action);

        Queue::assertPushed(SendDelayedProductNotification::class, function ($job) {
            return $job->applicationType === 'plantula' &&
                $job->delay->isSameDay(now()->addDay());
        });
    }

    public function test_it_dispatches_notification_for_delayed_reminder_14d()
    {
        Queue::fake();
        Carbon::setTestNow(now());
        $env = $this->createTestEnvironment();

        $action = Action::create([
            'tenant_id' => $env['tenant']->id,
            'indoor_id' => $env['indoor']->id,
            'action_type_id' => $env['actionType']->id,
            'action_date' => now(),
            'data' => [
                'product_application' => [
                    'application_type' => 'plague',
                    'reminder_time' => '14d',
                ]
            ]
        ]);

        ActionsResource::executeActionTrigger($action);

        Queue::assertPushed(SendDelayedProductNotification::class, function ($job) {
            return $job->applicationType === 'plague' &&
                $job->delay->isSameDay(now()->addDays(14));
        });
    }

    public function test_it_does_not_dispatch_notification_when_none_selected()
    {
        Queue::fake();
        $env = $this->createTestEnvironment();

        $action = Action::create([
            'tenant_id' => $env['tenant']->id,
            'indoor_id' => $env['indoor']->id,
            'action_type_id' => $env['actionType']->id,
            'action_date' => now(),
            'data' => [
                'product_application' => [
                    'application_type' => 'flora',
                    'reminder_time' => 'none',
                ]
            ]
        ]);

        ActionsResource::executeActionTrigger($action);

        Queue::assertNotPushed(SendDelayedProductNotification::class);
    }
}
