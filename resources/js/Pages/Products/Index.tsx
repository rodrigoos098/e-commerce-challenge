import React, { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import ProductGrid from '@/Components/Public/ProductGrid';
import CategoryFilter from '@/Components/Public/CategoryFilter';
import PriceFilter from '@/Components/Public/PriceFilter';
import SearchInput from '@/Components/Public/SearchInput';
import Pagination from '@/Components/Public/Pagination';
import type { ProductsPageProps, Category } from '@/types/public';

const DEFAULT_MIN_PRICE = 0;
const DEFAULT_MAX_PRICE = 10000;

// ——— Filters Panel (extracted to avoid remount on every parent render) ————

interface FiltersPanelProps {
  search: string;
  onSearchChange: (v: string) => void;
  categories: Category[];
  categoryId: number | string | null;
  onCategoryChange: (id: number | string | null) => void;
  priceMin: number;
  priceMax: number;
  onPriceChange: (min: number, max: number) => void;
  hasActiveFilters: boolean;
  onClearFilters: () => void;
}

const FiltersPanel = React.memo(function FiltersPanel({
  search,
  onSearchChange,
  categories,
  categoryId,
  onCategoryChange,
  priceMin,
  priceMax,
  onPriceChange,
  hasActiveFilters,
  onClearFilters,
}: FiltersPanelProps) {
  return (
    <aside className="space-y-8">
      {hasActiveFilters && (
        <button
          type="button"
          onClick={onClearFilters}
          className="w-full rounded-xl border border-warm-200 py-2 text-sm font-medium text-warm-600 hover:bg-warm-50 transition-colors"
        >
          Limpar todos os filtros
        </button>
      )}
      <div>
        <SearchInput value={search} onChange={onSearchChange} />
      </div>
      <div className="border-t border-warm-200 pt-6">
        <PriceFilter
          min={0}
          max={10000}
          currentMin={priceMin}
          currentMax={priceMax}
          onChange={onPriceChange}
        />
      </div>
      <div className="border-t border-warm-200 pt-6">
        <CategoryFilter categories={categories} selected={categoryId} onChange={onCategoryChange} />
      </div>
    </aside>
  );
});

// ——— Page Component ———————————————————————————————————————

export default function ProductsIndex({ products, categories, filters }: ProductsPageProps) {
  const currentFilters = filters ?? {};
  const currentPage = Number(currentFilters.page ?? 1);

  const [search, setSearch] = useState(currentFilters.search ?? '');
  const [categoryId, setCategoryId] = useState<number | string | null>(
    currentFilters.category_id ?? null
  );
  const [priceMin, setPriceMin] = useState(Number(currentFilters.price_min ?? DEFAULT_MIN_PRICE));
  const [priceMax, setPriceMax] = useState(Number(currentFilters.price_max ?? DEFAULT_MAX_PRICE));
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const didMountSearchEffect = useRef(false);

  const buildFilterParams = (
    overrides: Partial<
      Record<
        'search' | 'category_id' | 'price_min' | 'price_max' | 'page',
        number | string | null | undefined
      >
    > = {}
  ) => {
    const hasOverride = <T extends keyof typeof overrides>(key: T): boolean =>
      Object.prototype.hasOwnProperty.call(overrides, key);

    const nextSearch = hasOverride('search')
      ? overrides.search
        ? String(overrides.search)
        : undefined
      : search || undefined;
    const nextCategoryId = hasOverride('category_id')
      ? overrides.category_id == null || overrides.category_id === ''
        ? undefined
        : overrides.category_id
      : categoryId || undefined;
    const nextPriceMin = hasOverride('price_min')
      ? overrides.price_min == null || overrides.price_min === ''
        ? undefined
        : overrides.price_min
      : priceMin > DEFAULT_MIN_PRICE
        ? priceMin
        : undefined;
    const nextPriceMax = hasOverride('price_max')
      ? overrides.price_max == null || overrides.price_max === ''
        ? undefined
        : overrides.price_max
      : priceMax < DEFAULT_MAX_PRICE
        ? priceMax
        : undefined;
    const nextPage = hasOverride('page')
      ? overrides.page == null || overrides.page === ''
        ? undefined
        : overrides.page
      : currentPage > 1
        ? currentPage
        : undefined;

    return {
      search: nextSearch,
      category_id: nextCategoryId,
      price_min: nextPriceMin,
      price_max: nextPriceMax,
      page: nextPage,
    };
  };

  useEffect(() => {
    setSearch(currentFilters.search ?? '');
    setCategoryId(currentFilters.category_id ?? null);
    setPriceMin(Number(currentFilters.price_min ?? DEFAULT_MIN_PRICE));
    setPriceMax(Number(currentFilters.price_max ?? DEFAULT_MAX_PRICE));
  }, [
    currentFilters.category_id,
    currentFilters.price_max,
    currentFilters.price_min,
    currentFilters.search,
  ]);

  // Search debounce — only reacts to search changes; other filters call router.get directly
  useEffect(() => {
    if (!didMountSearchEffect.current) {
      didMountSearchEffect.current = true;

      return;
    }

    const timer = setTimeout(() => {
      router.get('/products', buildFilterParams({ page: undefined }), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
      });
    }, 400);
    return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  const handleCategoryChange = (id: number | string | null) => {
    setCategoryId(id);
    router.get('/products', buildFilterParams({ category_id: id, page: undefined }), {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    });
  };

  const handlePriceChange = (min: number, max: number) => {
    setPriceMin(min);
    setPriceMax(max);
    router.get(
      '/products',
      buildFilterParams({
        price_min: min > DEFAULT_MIN_PRICE ? min : undefined,
        price_max: max < DEFAULT_MAX_PRICE ? max : undefined,
        page: undefined,
      }),
      { preserveScroll: true, preserveState: true, replace: true }
    );
  };

  const handlePageChange = (page: number) => {
    const params: Record<string, string | number | undefined> = {};

    if (search) {
      params.search = search;
    }

    if (categoryId) {
      params.category_id = categoryId;
    }

    if (priceMin > DEFAULT_MIN_PRICE) {
      params.price_min = priceMin;
    }

    if (priceMax < DEFAULT_MAX_PRICE) {
      params.price_max = priceMax;
    }

    params.page = page;

    router.get('/products', params, {
      preserveScroll: false,
      preserveState: true,
    });
  };

  const hasActiveFilters = !!(
    categoryId ||
    search ||
    priceMin > DEFAULT_MIN_PRICE ||
    priceMax < DEFAULT_MAX_PRICE
  );

  const clearFilters = () => {
    setSearch('');
    setCategoryId(null);
    setPriceMin(DEFAULT_MIN_PRICE);
    setPriceMax(DEFAULT_MAX_PRICE);
    router.get('/products', {}, { preserveState: false, replace: true });
  };

  const filtersPanelProps: FiltersPanelProps = {
    search,
    onSearchChange: setSearch,
    categories,
    categoryId,
    onCategoryChange: handleCategoryChange,
    priceMin,
    priceMax,
    onPriceChange: handlePriceChange,
    hasActiveFilters,
    onClearFilters: clearFilters,
  };

  return (
    <PublicLayout
      title="Coleção"
      description="Explore a coleção Shopsugi com peças únicas de artesanato e design para presentear com significado."
    >
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        {/* Page header */}
        <div className="mb-6 flex items-center justify-between">
          <div>
            <h1 className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700">
              Nossa coleção
            </h1>
            <p className="mt-1 text-sm text-warm-500">
              {products.meta.total} resultado{products.meta.total !== 1 ? 's' : ''}
              {search && (
                <>
                  {' '}
                  para "<strong>{search}</strong>"
                </>
              )}
            </p>
          </div>

          {/* Mobile filter toggle */}
          <button
            type="button"
            onClick={() => setSidebarOpen((v) => !v)}
            className="lg:hidden flex items-center gap-2 rounded-xl border border-warm-200 bg-white px-4 py-2 text-sm font-medium text-warm-600 shadow-sm hover:bg-warm-50 transition-colors"
            aria-expanded={sidebarOpen}
            aria-controls="filters-sidebar"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
              />
            </svg>
            Filtros
            {hasActiveFilters && (
              <span className="flex h-5 w-5 items-center justify-center rounded-full bg-kintsugi-500 text-xs text-white font-bold">
                {
                  [
                    categoryId,
                    search,
                    priceMin > DEFAULT_MIN_PRICE,
                    priceMax < DEFAULT_MAX_PRICE,
                  ].filter(Boolean).length
                }
              </span>
            )}
          </button>
        </div>

        <div className="flex gap-8">
          {/* Desktop sidebar */}
          <div className="hidden lg:block w-56 shrink-0">
            <div className="sticky top-24">
              <FiltersPanel {...filtersPanelProps} />
            </div>
          </div>

          {/* Mobile sidebar overlay */}
          {sidebarOpen && (
            <>
              <div
                className="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm lg:hidden animate-fade-in"
                onClick={() => setSidebarOpen(false)}
                aria-hidden="true"
              />
              <div
                id="filters-sidebar"
                className="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto bg-white p-6 shadow-2xl lg:hidden animate-slide-in-left"
              >
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-base font-bold text-warm-700">Filtros</h2>
                  <button
                    type="button"
                    onClick={() => setSidebarOpen(false)}
                    aria-label="Fechar filtros"
                    className="text-warm-400 hover:text-warm-600"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      className="h-5 w-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2}
                      aria-hidden="true"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <FiltersPanel {...filtersPanelProps} />
              </div>
            </>
          )}

          {/* Products grid */}
          <div className="flex-1 min-w-0">
            <ProductGrid
              products={products.data}
              emptyMessage="Nenhum produto encontrado para os filtros selecionados."
              onClearFilters={hasActiveFilters ? clearFilters : undefined}
            />
            <Pagination meta={products.meta} onPageChange={handlePageChange} />
          </div>
        </div>
      </div>
    </PublicLayout>
  );
}
