import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { type Column } from '@/Components/Admin/DataTable';
import SearchBar from '@/Components/Admin/SearchBar';
import Modal from '@/Components/Admin/Modal';
import type { Product, Category, PaginatedResponse } from '@/types/admin';

// — Helpers ——————————————————————————
function formatCurrency(v: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
}

// — Props ——————————————————————————
interface ProductsIndexProps {
    products: PaginatedResponse<Product>;
    categories: Category[];
    filters?: {
        search?: string;
        category_id?: string;
        active?: string;
    };
}

// — Component ——————————————————————————
export default function ProductsIndex({
    products,
    categories,
    filters = {},
}: ProductsIndexProps) {
    const productData = products.data;
    const paginationMeta = products.meta;

    const [search, setSearch] = useState(filters.search ?? '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category_id ?? '');
    const [activeFilter, setActiveFilter] = useState(filters.active ?? '');
    const [deleteModal, setDeleteModal] = useState<{ open: boolean; product: Product | null }>({ open: false, product: null });
    const [deleting, setDeleting] = useState(false);

    function applyFilters(overrides: Record<string, string>) {
        router.get(
            '/admin/products',
            { search, category_id: categoryFilter, active: activeFilter, ...overrides },
            { preserveState: true, replace: true },
        );
    }

    function handleSearch(value: string) {
        setSearch(value);
        applyFilters({ search: value });
    }

    function handleCategoryChange(value: string) {
        setCategoryFilter(value);
        applyFilters({ category_id: value });
    }

    function handleActiveChange(value: string) {
        setActiveFilter(value);
        applyFilters({ active: value });
    }

    function confirmDelete(product: Product) {
        setDeleteModal({ open: true, product });
    }

    function handleDelete() {
        if (!deleteModal.product) { return; }
        setDeleting(true);
        router.delete(`/admin/products/${deleteModal.product.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteModal({ open: false, product: null });
            },
        });
    }

    const columns: Column<Product>[] = [
        {
            key: 'name',
            label: 'Produto',
            sortable: true,
            render: (row) => (
                <div>
                    <p className="font-medium text-gray-900">{row.name}</p>
                    <p className="text-xs text-gray-400 mt-0.5">{row.category.name}</p>
                </div>
            ),
        },
        {
            key: 'price',
            label: 'Preço',
            sortable: true,
            render: (row) => (
                <div>
                    <p className="font-medium text-gray-800">{formatCurrency(row.price)}</p>
                    {row.cost_price && (
                        <p className="text-xs text-gray-400">Custo: {formatCurrency(row.cost_price)}</p>
                    )}
                </div>
            ),
        },
        {
            key: 'quantity',
            label: 'Estoque',
            sortable: true,
            render: (row) => {
                const isLow = row.quantity <= row.min_quantity;
                const isOut = row.quantity === 0;
                return (
                    <div className="flex items-center gap-2">
                        <span className={[
                            'font-semibold',
                            isOut ? 'text-red-600' : isLow ? 'text-amber-600' : 'text-gray-800',
                        ].join(' ')}>
                            {row.quantity}
                        </span>
                        {isOut && (
                            <span className="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full font-medium">Esgotado</span>
                        )}
                        {!isOut && isLow && (
                            <span className="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-medium">Baixo</span>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'active',
            label: 'Status',
            render: (row) => (
                <span className={[
                    'inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full',
                    row.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500',
                ].join(' ')}>
                    <span className={['h-1.5 w-1.5 rounded-full', row.active ? 'bg-emerald-500' : 'bg-gray-400'].join(' ')} />
                    {row.active ? 'Ativo' : 'Inativo'}
                </span>
            ),
        },
        {
            key: 'actions',
            label: 'Ações',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <Link
                        href={`/admin/products/${row.id}`}
                        className="text-xs font-medium text-gray-600 hover:text-indigo-600 transition-colors px-2.5 py-1.5 rounded-md hover:bg-indigo-50"
                    >
                        Ver
                    </Link>
                    <Link
                        href={`/admin/products/${row.id}/edit`}
                        className="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors px-2.5 py-1.5 rounded-md hover:bg-indigo-50"
                    >
                        Editar
                    </Link>
                    <button
                        onClick={() => confirmDelete(row)}
                        className="text-xs font-medium text-red-600 hover:text-red-700 transition-colors px-2.5 py-1.5 rounded-md hover:bg-red-50"
                    >
                        Excluir
                    </button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Produtos">
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Produtos</h1>
                        <p className="text-sm text-gray-500 mt-0.5">
                            {paginationMeta.total} produto{paginationMeta.total !== 1 ? 's' : ''} cadastrado{paginationMeta.total !== 1 ? 's' : ''}
                        </p>
                    </div>
                    <Link
                        href="/admin/products/create"
                        className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Produto
                    </Link>
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row gap-3">
                    <SearchBar
                        onSearch={handleSearch}
                        initialValue={search}
                        placeholder="Buscar por nome..."
                        className="sm:w-72"
                    />
                    <select
                        value={categoryFilter}
                        onChange={(e) => handleCategoryChange(e.target.value)}
                        className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="">Todas as categorias</option>
                        {categories.map((cat) => (
                            <option key={cat.id} value={cat.id}>{cat.name}</option>
                        ))}
                    </select>
                    <select
                        value={activeFilter}
                        onChange={(e) => handleActiveChange(e.target.value)}
                        className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="">Todos os status</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>

                {/* Table */}
                <DataTable
                    columns={columns}
                    data={productData}
                    pagination={paginationMeta}
                    emptyMessage="Nenhum produto encontrado com os filtros aplicados."
                />
            </div>

            {/* Delete confirmation modal */}
            <Modal
                isOpen={deleteModal.open}
                onClose={() => setDeleteModal({ open: false, product: null })}
                title="Excluir Produto"
                onConfirm={handleDelete}
                confirmLabel="Excluir"
                cancelLabel="Cancelar"
                confirmDestructive
                loading={deleting}
            >
                <p className="text-sm text-gray-700">
                    Tem certeza que deseja excluir o produto{' '}
                    <strong className="font-semibold">{deleteModal.product?.name}</strong>?
                    Esta ação não pode ser desfeita.
                </p>
                <p className="text-xs text-gray-500 mt-2">
                    Todos os dados relacionados (movimentações de estoque, itens em pedidos) serão mantidos para histórico.
                </p>
            </Modal>
        </AdminLayout>
    );
}
