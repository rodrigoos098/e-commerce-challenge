<?php

namespace Tests\Unit\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
    }

    // ── PaginateForUser ───────────────────────────────────────────────────────

    public function test_paginate_for_user_returns_only_that_users_orders(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user1->id]);
        Order::factory()->create(['user_id' => $user2->id]);

        $result = $this->repository->paginateForUser($user1->id);

        $this->assertCount(2, $result->items());
    }

    public function test_paginate_for_user_respects_per_page(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['user_id' => $user->id]);

        $result = $this->repository->paginateForUser($user->id, 2);

        $this->assertCount(2, $result->items());
        $this->assertEquals(5, $result->total());
    }

    // ── Paginate (Admin) ──────────────────────────────────────────────────────

    public function test_paginate_returns_all_orders(): void
    {
        Order::factory()->count(3)->create();

        $result = $this->repository->paginate();

        $this->assertCount(3, $result->items());
    }

    public function test_paginate_with_status_filter(): void
    {
        Order::factory()->pending()->create();
        Order::factory()->delivered()->create();

        $result = $this->repository->paginate(['status' => 'pending']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('pending', $result->items()[0]->status);
    }

    public function test_paginate_with_user_id_filter(): void
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(); // different user

        $result = $this->repository->paginate(['user_id' => $user->id]);

        $this->assertCount(1, $result->items());
    }

    public function test_paginate_with_search_matches_order_id_name_and_email(): void
    {
        $matchingUser = User::factory()->create([
            'name' => 'Alice Search',
            'email' => 'alice@example.com',
        ]);
        $matchingOrder = Order::factory()->create(['user_id' => $matchingUser->id]);
        Order::factory()->create();

        $byId = $this->repository->paginate(['search' => (string) $matchingOrder->id]);
        $byName = $this->repository->paginate(['search' => 'Alice']);
        $byEmail = $this->repository->paginate(['search' => 'alice@example.com']);

        $this->assertCount(1, $byId->items());
        $this->assertSame($matchingOrder->id, $byId->items()[0]->id);
        $this->assertCount(1, $byName->items());
        $this->assertSame($matchingOrder->id, $byName->items()[0]->id);
        $this->assertCount(1, $byEmail->items());
        $this->assertSame($matchingOrder->id, $byEmail->items()[0]->id);
    }

    // ── FindById ──────────────────────────────────────────────────────────────

    public function test_find_by_id_returns_order(): void
    {
        $order = Order::factory()->create();

        $found = $this->repository->findById($order->id);

        $this->assertNotNull($found);
        $this->assertEquals($order->id, $found->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $found = $this->repository->findById(9999);

        $this->assertNull($found);
    }

    public function test_find_by_id_eager_loads_user_and_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $found = $this->repository->findById($order->id);

        $this->assertTrue($found->relationLoaded('user'));
        $this->assertTrue($found->relationLoaded('items'));
    }

    // ── FindByIdForUser ───────────────────────────────────────────────────────

    public function test_find_by_id_for_user_returns_matching_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $found = $this->repository->findByIdForUser($order->id, $user->id);

        $this->assertNotNull($found);
        $this->assertEquals($order->id, $found->id);
    }

    public function test_find_by_id_for_user_returns_null_for_other_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        $found = $this->repository->findByIdForUser($order->id, $user2->id);

        $this->assertNull($found);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_order_with_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00]);

        $orderData = [
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 5.00,
            'shipping_cost' => 0,
            'total' => 55.00,
            'shipping_address' => ['street' => '123 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            'billing_address' => ['street' => '123 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        ];

        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50.00,
                'total_price' => 50.00,
            ],
        ];

        $order = $this->repository->create($orderData, $items);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'pending']);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id]);
        $this->assertCount(1, $order->items);
    }

    public function test_create_order_with_multiple_items(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 30.00, 'slug' => 'p1']);
        $product2 = Product::factory()->create(['price' => 70.00, 'slug' => 'p2']);

        $orderData = [
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping_cost' => 0,
            'total' => 110.00,
            'shipping_address' => ['street' => '1 St', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            'billing_address' => ['street' => '1 St', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        ];

        $items = [
            ['product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 30.00, 'total_price' => 30.00],
            ['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 70.00, 'total_price' => 70.00],
        ];

        $order = $this->repository->create($orderData, $items);

        $this->assertCount(2, $order->items);
    }

    // ── UpdateStatus ─────────────────────────────────────────────────────────

    public function test_update_status_changes_order_status(): void
    {
        $order = Order::factory()->pending()->create();

        $updated = $this->repository->updateStatus($order, 'shipped');

        $this->assertEquals('shipped', $updated->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }

    public function test_daily_summary_returns_each_day_with_counts_and_revenue(): void
    {
        Order::factory()->create([
            'status' => 'delivered',
            'total' => 100,
            'created_at' => now()->subDays(1),
        ]);
        Order::factory()->create([
            'status' => 'cancelled',
            'total' => 999,
            'created_at' => now()->subDays(1),
        ]);

        $summary = $this->repository->dailySummary(2);

        $this->assertCount(2, $summary);
        $this->assertSame(now()->subDay()->toDateString(), $summary[0]['date']);
        $this->assertSame(1, $summary[0]['orders']);
        $this->assertSame(100.0, $summary[0]['revenue']);
        $this->assertSame(now()->toDateString(), $summary[1]['date']);
    }
}
