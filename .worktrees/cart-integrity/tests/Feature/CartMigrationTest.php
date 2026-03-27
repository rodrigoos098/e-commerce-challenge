<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_unique_migration_merges_duplicate_carts_and_handles_legacy_index_names(): void
    {
        $migration = require base_path('database/migrations/2026_03_26_000001_add_unique_user_id_index_to_carts_table.php');

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropUnique('carts_user_id_unique');
            $table->index('user_id', 'legacy_carts_user_lookup');
        });

        $user = User::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $productC = Product::factory()->create();

        $primaryCart = Cart::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinute(),
        ]);
        $duplicateCart = Cart::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        CartItem::factory()->create([
            'cart_id' => $primaryCart->id,
            'product_id' => $productA->id,
            'quantity' => 1,
        ]);
        CartItem::factory()->create([
            'cart_id' => $primaryCart->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'cart_id' => $duplicateCart->id,
            'product_id' => $productA->id,
            'quantity' => 3,
        ]);
        CartItem::factory()->create([
            'cart_id' => $duplicateCart->id,
            'product_id' => $productC->id,
            'quantity' => 4,
        ]);

        $migration->up();

        $this->assertSame(1, Cart::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('carts', ['id' => $primaryCart->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('carts', ['id' => $duplicateCart->id]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $primaryCart->id,
            'product_id' => $productA->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $primaryCart->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $primaryCart->id,
            'product_id' => $productC->id,
            'quantity' => 4,
        ]);

        $this->expectException(QueryException::class);

        Cart::factory()->create(['user_id' => $user->id]);
    }
}
