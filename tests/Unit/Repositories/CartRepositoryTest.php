<?php

namespace Tests\Unit\Repositories;

use App\Models\Cart;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PDOException;
use Tests\TestCase;

class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CartRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CartRepository();
    }

    public function test_find_by_user_id_throws_when_duplicate_carts_exist(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropUnique('carts_user_id_unique');
            $table->index('user_id');
        });

        $user = User::factory()->create();

        Cart::factory()->create(['user_id' => $user->id]);
        Cart::factory()->create(['user_id' => $user->id]);

        $this->expectException(MultipleRecordsFoundException::class);

        $this->repository->findByUserId($user->id);
    }

    public function test_find_or_create_for_user_recovers_after_unique_key_race(): void
    {
        $user = User::factory()->create();
        $existingCart = Cart::factory()->make(['user_id' => $user->id]);

        $repository = Mockery::mock(CartRepository::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $repository->shouldReceive('findByUserId')
            ->twice()
            ->with($user->id)
            ->andReturn(null, $existingCart);

        $repository->shouldReceive('createCart')
            ->once()
            ->with($user->id)
            ->andThrow($this->makeUniqueCartQueryException());

        $result = $repository->findOrCreateForUser($user->id);

        $this->assertSame($existingCart, $result);
    }

    private function makeUniqueCartQueryException(): QueryException
    {
        $previous = new PDOException('UNIQUE constraint failed: carts.user_id', '23000');
        $previous->errorInfo = ['23000', 19, 'UNIQUE constraint failed: carts.user_id'];

        return new QueryException(
            'mysql',
            'insert into "carts" ("user_id") values (?)',
            [1],
            $previous,
        );
    }
}
