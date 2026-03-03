import React, { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import ProductGrid from '@/Components/Public/ProductGrid';
import CategoryFilter from '@/Components/Public/CategoryFilter';
import PriceFilter from '@/Components/Public/PriceFilter';
import SearchInput from '@/Components/Public/SearchInput';
import Pagination from '@/Components/Public/Pagination';
import type { ProductsPageProps, Category, Product, PaginatedResponse } from '@/types/public';

// ——— Mock data ——————————————————————————————————————————————
const MOCK_CATEGORIES: Category[] = [
    { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null, children: [
        { id: 11, name: 'Smartphones', slug: 'smartphones', active: true, parent_id: 1 },
        { id: 12, name: 'Notebooks', slug: 'notebooks', active: true, parent_id: 1 },
    ] },
    { id: 2, name: 'Roupas', slug: 'roupas', active: true, parent_id: null },
    { id: 3, name: 'Esportes', slug: 'esportes', active: true, parent_id: null },
    { id: 4, name: 'Casa & Jardim', slug: 'casa-jardim', active: true, parent_id: null },
    { id: 5, name: 'Livros', slug: 'livros', active: true, parent_id: null },
];

const MOCK_PRODUCTS: Product[] = Array.from({ length: 12 }, (_, i) => ({
    id: i + 1,
    name: [
        'Fone de Ouvido Bluetooth Premium',
        'Camiseta Oversized Classic',
        'Tênis Running Pro 3000',
        'Smart Watch Series X',
        'Livro: Clean Code',
        'Mochila Ultraleve 30L',
        'Câmera DSLR 24MP Full Frame',
        'Cadeira Gamer ErgoMax',
        'Monitor 4K 27 polegadas',
        'Teclado Mecânico RGB',
        'Mouse Gamer 12000 DPI',
        'Headset 7.1 Surround',
    ][i],
    slug: `produto-${i + 1}`,
    description: 'Produto de alta qualidade com garantia.',
    price: [299.9, 89.9, 349.9, 799.9, 69.9, 249.9, 3999.9, 1299.9, 2199.9, 399.9, 179.9, 499.9][i],
    quantity: [50, 100, 30, 15, 200, 80, 8, 25, 12, 60, 90, 35][i],
    min_quantity: 5,
    active: true,
    category: MOCK_CATEGORIES[i % MOCK_CATEGORIES.length],
    tags: [],
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
}));

const MOCK_PAGINATED: PaginatedResponse<Product> = {
    data: MOCK_PRODUCTS,
    meta: { current_page: 1, per_page: 15, total: 48, last_page: 4 },
    links: { first: null, last: null, prev: null, next: null },
};

// ——— Page Component ———————————————————————————————————————

export default function ProductsIndex({ products, categories, filters }: Partial<ProductsPageProps>) {
    const pageData = products ?? MOCK_PAGINATED;
    const cats = categories ?? MOCK_CATEGORIES;
    const currentFilters = filters ?? {};

    const [search, setSearch] = useState(currentFilters.search ?? '');
    const [categoryId, setCategoryId] = useState<number | string | null>(currentFilters.category_id ?? null);
    const [priceMin, setPriceMin] = useState(Number(currentFilters.price_min ?? 0));
    const [priceMax, setPriceMax] = useState(Number(currentFilters.price_max ?? 10000));
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const applyFilters = useCallback(
        (overrides: Record<string, unknown> = {}) => {
            router.get(
                '/products',
                {
                    search: search || undefined,
                    category_id: categoryId || undefined,
                    price_min: priceMin > 0 ? priceMin : undefined,
                    price_max: priceMax < 10000 ? priceMax : undefined,
                    ...overrides,
                },
                { preserveScroll: true, preserveState: true, replace: true },
            );
        },
        [search, categoryId, priceMin, priceMax],
    );

    // Search debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            applyFilters({ search });
        }, 400);

        return () => clearTimeout(timer);
    }, [applyFilters, search]);

    const handleCategoryChange = (id: number | string | null) => {
        setCategoryId(id);
        router.get(
            '/products',
            { category_id: id || undefined, search: search || undefined },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const handlePriceChange = (min: number, max: number) => {
        setPriceMin(min);
        setPriceMax(max);
        router.get(
            '/products',
            { price_min: min > 0 ? min : undefined, price_max: max < 10000 ? max : undefined, category_id: categoryId || undefined, search: search || undefined },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const handlePageChange = (page: number) => {
        router.get(
            '/products',
            { page, category_id: categoryId || undefined, search: search || undefined },
            { preserveScroll: false, preserveState: false },
        );
    };

    const hasActiveFilters = !!(categoryId || search || priceMin > 0 || priceMax < 10000);

    const clearFilters = () => {
        setSearch('');
        setCategoryId(null);
        setPriceMin(0);
        setPriceMax(10000);
        router.get('/products', {}, { preserveState: false, replace: true });
    };

    // Sidebar filters
    const FiltersPanel = () => (
        <aside className="space-y-8">
            <div>
                <SearchInput value={search} onChange={setSearch} />
            </div>
            <div className="border-t border-gray-100 pt-6">
                <CategoryFilter categories={cats} selected={categoryId} onChange={handleCategoryChange} />
            </div>
            <div className="border-t border-gray-100 pt-6">
                <PriceFilter
                    min={0}
                    max={10000}
                    currentMin={priceMin}
                    currentMax={priceMax}
                    onChange={handlePriceChange}
                />
            </div>
            {hasActiveFilters && (
                <button
                    type="button"
                    onClick={clearFilters}
                    className="w-full rounded-xl border border-gray-200 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    Limpar todos os filtros
                </button>
            )}
        </aside>
    );

    return (
        <PublicLayout title="Produtos">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
                {/* Page header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900">Produtos</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {pageData.meta.total} resultado{pageData.meta.total !== 1 ? 's' : ''}
                            {search && <> para "<strong>{search}</strong>"</>}
                        </p>
                    </div>

                    {/* Mobile filter toggle */}
                    <button
                        type="button"
                        onClick={() => setSidebarOpen((v) => !v)}
                        className="lg:hidden flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                        aria-expanded={sidebarOpen}
                        aria-controls="filters-sidebar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filtros
                        {hasActiveFilters && (
                            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-violet-600 text-xs text-white font-bold">
                                !
                            </span>
                        )}
                    </button>
                </div>

                <div className="flex gap-8">
                    {/* Desktop sidebar */}
                    <div className="hidden lg:block w-56 shrink-0">
                        <div className="sticky top-24">
                            <FiltersPanel />
                        </div>
                    </div>

                    {/* Mobile sidebar overlay */}
                    {sidebarOpen && (
                        <>
                            <div
                                className="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm lg:hidden"
                                onClick={() => setSidebarOpen(false)}
                                aria-hidden="true"
                            />
                            <div
                                id="filters-sidebar"
                                className="fixed inset-y-0 left-0 z-50 w-72 overflow-y-auto bg-white p-6 shadow-2xl lg:hidden"
                            >
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-base font-bold text-gray-900">Filtros</h2>
                                    <button
                                        type="button"
                                        onClick={() => setSidebarOpen(false)}
                                        aria-label="Fechar filtros"
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <FiltersPanel />
                            </div>
                        </>
                    )}

                    {/* Products grid */}
                    <div className="flex-1 min-w-0">
                        <ProductGrid products={pageData.data} emptyMessage="Nenhum produto encontrado para os filtros selecionados." />
                        <Pagination meta={pageData.meta} onPageChange={handlePageChange} />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
