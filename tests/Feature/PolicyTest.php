<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private ProductPolicy $productPolicy;

    private OrderPolicy $orderPolicy;

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

        $this->productPolicy = new ProductPolicy();
        $this->orderPolicy = new OrderPolicy();
    }

    // ── ProductPolicy ──────────────────────────────────────────────────────────────

    public function test_product_policy_view_any_returns_true_for_guest(): void
    {
        $this->assertTrue($this->productPolicy->viewAny(null));
    }

    public function test_product_policy_view_any_returns_true_for_authenticated(): void
    {
        $this->assertTrue($this->productPolicy->viewAny($this->customer));
    }

    public function test_product_policy_view_returns_true_for_guest(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($this->productPolicy->view(null, $product));
    }

    public function test_product_policy_create_allows_admin(): void
    {
        $this->assertTrue($this->productPolicy->create($this->admin));
    }

    public function test_product_policy_create_denies_customer(): void
    {
        $this->assertFalse($this->productPolicy->create($this->customer));
    }

    public function test_product_policy_update_allows_admin(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($this->productPolicy->update($this->admin, $product));
    }

    public function test_product_policy_update_denies_customer(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($this->productPolicy->update($this->customer, $product));
    }

    public function test_product_policy_delete_allows_admin(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($this->productPolicy->delete($this->admin, $product));
    }

    public function test_product_policy_delete_denies_customer(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($this->productPolicy->delete($this->customer, $product));
    }

    public function test_product_policy_restore_allows_admin(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($this->productPolicy->restore($this->admin, $product));
    }

    public function test_product_policy_force_delete_allows_admin(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($this->productPolicy->forceDelete($this->admin, $product));
    }

    // ── OrderPolicy ────────────────────────────────────────────────────────────────

    public function test_order_policy_view_any_returns_true_for_any_user(): void
    {
        $this->assertTrue($this->orderPolicy->viewAny($this->admin));
        $this->assertTrue($this->orderPolicy->viewAny($this->customer));
    }

    public function test_order_policy_view_allows_admin_to_see_any_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue($this->orderPolicy->view($this->admin, $order));
    }

    public function test_order_policy_view_allows_customer_to_see_own_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertTrue($this->orderPolicy->view($this->customer, $order));
    }

    public function test_order_policy_view_denies_customer_seeing_other_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->orderPolicy->view($this->customer, $order));
    }

    public function test_order_policy_create_allows_any_authenticated_user(): void
    {
        $this->assertTrue($this->orderPolicy->create($this->admin));
        $this->assertTrue($this->orderPolicy->create($this->customer));
    }

    public function test_order_policy_update_allows_admin(): void
    {
        $order = Order::factory()->create();

        $this->assertTrue($this->orderPolicy->update($this->admin, $order));
    }

    public function test_order_policy_update_denies_customer(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertFalse($this->orderPolicy->update($this->customer, $order));
    }

    public function test_order_policy_delete_allows_admin(): void
    {
        $order = Order::factory()->create();

        $this->assertTrue($this->orderPolicy->delete($this->admin, $order));
    }

    public function test_order_policy_delete_denies_customer(): void
    {
        $order = Order::factory()->create(['user_id' => $this->customer->id]);

        $this->assertFalse($this->orderPolicy->delete($this->customer, $order));
    }
}
