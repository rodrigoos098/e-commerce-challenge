<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Jobs\ProcessOrderPipeline;
use App\Jobs\SendOrderConfirmationEmail;
use App\Listeners\ProcessOrderListener;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderProcessingPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_order_created_listener_dispatches_processing_pipeline_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $order = Order::factory()->for($user)->create();

        app(ProcessOrderListener::class)->handle(new OrderCreated($order));

        Queue::assertPushed(ProcessOrderPipeline::class, function (ProcessOrderPipeline $job) use ($order): bool {
            return $job->orderId === $order->id;
        });
    }

    public function test_processing_pipeline_does_not_dispatch_notification_jobs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        app()->call([new ProcessOrderPipeline($order->id), 'handle']);

        $this->assertSame('pending', $order->fresh()->status);

        Queue::assertNotPushed(SendOrderConfirmationEmail::class);
    }

    public function test_processing_pipeline_is_retry_safe_for_pending_orders(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        app()->call([new ProcessOrderPipeline($order->id), 'handle']);
        app()->call([new ProcessOrderPipeline($order->id), 'handle']);

        $this->assertSame('pending', $order->fresh()->status);
    }
}
