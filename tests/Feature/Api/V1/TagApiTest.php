<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    public function test_guest_can_list_tags(): void
    {
        Tag::factory()->count(2)->create();

        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_tag(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/tags', [
                'name' => 'Lancamento',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Lancamento');

        $this->assertDatabaseHas('tags', [
            'name' => 'Lancamento',
            'slug' => 'lancamento',
        ]);
    }

    public function test_customer_cannot_create_tag(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/tags', [
                'name' => 'Bloqueada',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_tag(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::factory()->create([
            'name' => 'Oferta',
            'slug' => 'oferta',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/tags/{$tag->id}", [
                'name' => 'Oferta Especial',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Oferta Especial')
            ->assertJsonPath('data.slug', 'oferta-especial');
    }

    public function test_admin_can_delete_tag(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_tags_index_exposes_product_count_for_merchandising(): void
    {
        $tag = Tag::factory()->create(['name' => 'Popular']);
        $products = Product::factory()->count(2)->create();
        $tag->products()->attach($products->pluck('id'));

        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonPath('data.0.products_count', 2);
    }
}
