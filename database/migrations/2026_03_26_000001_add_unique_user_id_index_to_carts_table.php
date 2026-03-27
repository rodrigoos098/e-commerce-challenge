<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->mergeDuplicateUserCarts();

        if (DB::getDriverName() !== 'mysql') {
            $this->dropUserIdIndexes(unique: false);
        }

        if (! $this->hasUserIdIndex(unique: true)) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->unique('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropUserIdIndexes(unique: true);

        if (! $this->hasUserIdIndex(unique: false)) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->index('user_id');
            });
        }
    }

    protected function mergeDuplicateUserCarts(): void
    {
        DB::transaction(function (): void {
            DB::table('carts')
                ->select(['id', 'user_id'])
                ->whereNotNull('user_id')
                ->orderBy('user_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->groupBy('user_id')
                ->each(function (Collection $carts): void {
                    if ($carts->count() < 2) {
                        return;
                    }

                    $primaryCartId = (int) $carts->first()->id;

                    $carts->slice(1)->each(function (object $duplicateCart) use ($primaryCartId): void {
                        $this->mergeCartItems((int) $duplicateCart->id, $primaryCartId);

                        DB::table('carts')
                            ->where('id', $duplicateCart->id)
                            ->delete();
                    });
                });
        });
    }

    protected function mergeCartItems(int $sourceCartId, int $targetCartId): void
    {
        DB::table('cart_items')
            ->select(['id', 'product_id', 'quantity'])
            ->where('cart_id', $sourceCartId)
            ->orderBy('id')
            ->get()
            ->each(function (object $sourceItem) use ($targetCartId): void {
                $matchingTargetItem = DB::table('cart_items')
                    ->select(['id', 'quantity'])
                    ->where('cart_id', $targetCartId)
                    ->where('product_id', $sourceItem->product_id)
                    ->first();

                if ($matchingTargetItem) {
                    DB::table('cart_items')
                        ->where('id', $matchingTargetItem->id)
                        ->update([
                            'quantity' => (int) $matchingTargetItem->quantity + (int) $sourceItem->quantity,
                        ]);

                    DB::table('cart_items')
                        ->where('id', $sourceItem->id)
                        ->delete();

                    return;
                }

                DB::table('cart_items')
                    ->where('id', $sourceItem->id)
                    ->update(['cart_id' => $targetCartId]);
            });
    }

    protected function hasUserIdIndex(bool $unique): bool
    {
        return Collection::make($this->getUserIdIndexes())
            ->contains(fn (array $index): bool => $index['unique'] === $unique);
    }

    protected function dropUserIdIndexes(bool $unique): void
    {
        Collection::make($this->getUserIdIndexes())
            ->filter(fn (array $index): bool => $index['unique'] === $unique)
            ->each(fn (array $index): bool => $this->dropIndexByName($index['name']));
    }

    /**
     * @return list<array{name: string, unique: bool, columns: list<string>}>
     */
    protected function getUserIdIndexes(): array
    {
        return Collection::make($this->getIndexesForCurrentDriver())
            ->filter(fn (array $index): bool => $index['columns'] === ['user_id'])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, unique: bool, columns: list<string>}>
     */
    protected function getIndexesForCurrentDriver(): array
    {
        return match (DB::getDriverName()) {
            'sqlite' => $this->getSqliteIndexes(),
            'mysql' => $this->getMysqlIndexes(),
            'pgsql' => $this->getPgsqlIndexes(),
            default => [],
        };
    }

    /**
     * @return list<array{name: string, unique: bool, columns: list<string>}>
     */
    protected function getSqliteIndexes(): array
    {
        return Collection::make(DB::select("PRAGMA index_list('carts')"))
            ->map(function (object $index): array {
                $columns = Collection::make(DB::select("PRAGMA index_info('{$index->name}')"))
                    ->sortBy('seqno')
                    ->pluck('name')
                    ->values()
                    ->all();

                return [
                    'name' => (string) $index->name,
                    'unique' => (bool) $index->unique,
                    'columns' => $columns,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, unique: bool, columns: list<string>}>
     */
    protected function getMysqlIndexes(): array
    {
        return Collection::make(DB::select('SHOW INDEX FROM `carts`'))
            ->groupBy('Key_name')
            ->map(function (Collection $indexRows, string $name): array {
                $orderedRows = $indexRows->sortBy('Seq_in_index')->values();

                return [
                    'name' => $name,
                    'unique' => (int) $orderedRows->first()->Non_unique === 0,
                    'columns' => $orderedRows->pluck('Column_name')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, unique: bool, columns: list<string>}>
     */
    protected function getPgsqlIndexes(): array
    {
        return Collection::make(DB::select(<<<'SQL'
            select
                indexname,
                indexdef
            from pg_indexes
            where schemaname = current_schema()
              and tablename = 'carts'
        SQL))
            ->map(function (object $index): array {
                preg_match('/\(([^)]+)\)/', $index->indexdef, $matches);

                $columns = Collection::make(explode(',', $matches[1] ?? ''))
                    ->map(fn (string $column): string => trim($column, ' "'))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'name' => (string) $index->indexname,
                    'unique' => str_contains(strtoupper($index->indexdef), 'CREATE UNIQUE INDEX'),
                    'columns' => $columns,
                ];
            })
            ->values()
            ->all();
    }

    protected function dropIndexByName(string $indexName): bool
    {
        $wrappedIndexName = match (DB::getDriverName()) {
            'mysql' => "`{$indexName}`",
            default => '"'.$indexName.'"',
        };

        return match (DB::getDriverName()) {
            'mysql' => DB::statement("DROP INDEX {$wrappedIndexName} ON `carts`"),
            'sqlite', 'pgsql' => DB::statement("DROP INDEX IF EXISTS {$wrappedIndexName}"),
            default => false,
        };
    }
};
