<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These tests cover direct Gate / policy decisions only.
     * HTTP integration and middleware behavior belong in AuthorizationTest.
     */

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    public function test_product_policy_create_allows_admin_via_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Product::class));
    }

    public function test_product_policy_create_denies_customer_via_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->customer)->denies('create', Product::class));
    }

    public function test_product_policy_update_allows_admin_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $product));
    }

    public function test_product_policy_update_denies_customer_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->customer)->denies('update', $product));
    }

    public function test_product_policy_delete_allows_admin_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $product));
    }

    public function test_product_policy_delete_denies_customer_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->customer)->denies('delete', $product));
    }

    public function test_product_policy_restore_allows_admin_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('restore', $product));
    }

    public function test_product_policy_force_delete_allows_admin_via_gate(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('forceDelete', $product));
    }

    public function test_order_policy_view_any_allows_authenticated_users_via_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Order::class));
        $this->assertTrue(Gate::forUser($this->customer)->allows('viewAny', Order::class));
    }

    public function test_order_policy_view_allows_admin_to_see_any_order_via_gate(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $order));
    }

    public function test_order_policy_view_allows_customer_to_see_own_order_via_gate(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue(Gate::forUser($this->customer)->allows('view', $order));
    }

    public function test_order_policy_view_denies_customer_seeing_other_order_via_gate(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $this->assertTrue(Gate::forUser($this->customer)->denies('view', $order));
    }

    public function test_order_policy_create_allows_any_authenticated_user_via_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('create', Order::class));
        $this->assertTrue(Gate::forUser($this->customer)->allows('create', Order::class));
    }

    public function test_order_policy_update_allows_admin_via_gate(): void
    {
        $order = Order::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $order));
    }

    public function test_order_policy_update_denies_customer_via_gate(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue(Gate::forUser($this->customer)->denies('update', $order));
    }

    public function test_order_policy_delete_allows_admin_via_gate(): void
    {
        $order = Order::factory()->create();

        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $order));
    }

    public function test_order_policy_delete_denies_customer_via_gate(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue(Gate::forUser($this->customer)->denies('delete', $order));
    }
}
