import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/Admin/DataTable';
import type { PaginationMeta } from '@/Components/Admin/DataTable';
import StatusBadge from '@/Components/Admin/StatusBadge';
import SearchBar from '@/Components/Admin/SearchBar';
import type { OrderStatus } from '@/types/admin';

// — Extended type (OrderItem from backend includes user relation) ——————————————
interface OrderRow {
    id: number;
    status: OrderStatus;
    total: number;
    created_at: string;
    user?: { id: number; name: string; email: string } | null;
}

interface OrdersIndexProps {
    orders?: {
        data: OrderRow[];
        meta: PaginationMeta;
    };
    filters?: { search?: string; status?: string };
}

// — Mock data ——————————————————————————————————
const MOCK_ORDERS: OrderRow[] = [
    { id: 1, status: 'pending',    total: 1299.90, created_at: '2025-01-10T14:30:00Z', user: { id: 1, name: 'Ana Souza',      email: 'ana@example.com'    } },
    { id: 2, status: 'processing', total:  459.50, created_at: '2025-01-09T10:15:00Z', user: { id: 2, name: 'Bruno Lima',     email: 'bruno@example.com'  } },
    { id: 3, status: 'shipped',    total:  789.00, created_at: '2025-01-08T08:00:00Z', user: { id: 3, name: 'Carla Dias',     email: 'carla@example.com'  } },
    { id: 4, status: 'delivered',  total: 2350.00, created_at: '2025-01-05T16:45:00Z', user: { id: 4, name: 'Diego Mota',     email: 'diego@example.com'  } },
    { id: 5, status: 'cancelled',  total:  129.90, created_at: '2025-01-04T11:20:00Z', user: { id: 5, name: 'Elisa Ferreira', email: 'elisa@example.com'  } },
];

const MOCK_PAGINATION = {
    data: MOCK_ORDERS,
    meta: {
        current_page: 1,
        last_page: 3,
        per_page: 15,
        total: 42,
        links: [
            { url: null,                   label: '&laquo; Anterior', active: false },
            { url: '/admin/orders?page=1', label: '1',               active: true  },
            { url: '/admin/orders?page=2', label: '2',               active: false },
            { url: '/admin/orders?page=2', label: 'Próximo &raquo;', active: false },
        ],
    },
};

// — Constants ——————————————————————
const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: '',           label: 'Todos os status' },
    { value: 'pending',    label: 'Pendente'        },
    { value: 'processing', label: 'Processando'     },
    { value: 'shipped',    label: 'Enviado'         },
    { value: 'delivered',  label: 'Entregue'        },
    { value: 'cancelled',  label: 'Cancelado'       },
];

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatCurrency(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// — Component ——————————————————————
export default function OrdersIndex({ orders = MOCK_PAGINATION, filters = {} }: OrdersIndexProps) {
    const [statusFilter, setStatusFilter] = useState(filters.status ?? '');

    function applyFilters(updates: Record<string, string>) {
        router.get('/admin/orders', { ...(filters ?? {}), ...updates }, { preserveState: true, replace: true });
    }

    const columns = [
        {
            key: 'id',
            label: 'Pedido',
            sortable: true,
            render: (o: OrderRow) => (
                <span className="font-mono font-medium text-gray-900">#{String(o.id).padStart(5, '0')}</span>
            ),
        },
        {
            key: 'user',
            label: 'Cliente',
            render: (o: OrderRow) => (
                <div>
                    <p className="text-sm font-medium text-gray-900">{o.user?.name ?? '—'}</p>
                    <p className="text-xs text-gray-500">{o.user?.email ?? ''}</p>
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (o: OrderRow) => <StatusBadge status={o.status} />,
        },
        {
            key: 'total',
            label: 'Total',
            sortable: true,
            render: (o: OrderRow) => <span className="font-semibold text-gray-900">{formatCurrency(o.total)}</span>,
        },
        {
            key: 'created_at',
            label: 'Data',
            sortable: true,
            render: (o: OrderRow) => <span className="text-sm text-gray-500">{formatDate(o.created_at)}</span>,
        },
        {
            key: 'actions',
            label: '',
            render: (o: OrderRow) => (
                <div className="flex items-center justify-end">
                    {(o.status === 'pending' || o.status === 'processing') ? (
                        <button
                            type="button"
                            onClick={() => router.visit(`/admin/orders/${o.id}`)}
                            className="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md transition-colors"
                        >
                            Gerenciar
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={() => router.visit(`/admin/orders/${o.id}`)}
                            className="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                        >
                            Ver Detalhes
                        </button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Pedidos">
            <div className="p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Pedidos</h1>
                    <p className="text-sm text-gray-500 mt-0.5">{orders.meta.total} pedidos encontrados</p>
                </div>

                {/* Status pills */}
                <div className="flex flex-wrap gap-2">
                    {STATUS_OPTIONS.filter((s) => s.value !== '').map((s) => {
                        const count = MOCK_ORDERS.filter((o) => o.status === s.value).length;
                        const active = statusFilter === s.value;
                        return (
                            <button
                                key={s.value}
                                type="button"
                                onClick={() => {
                                    const next = active ? '' : s.value;
                                    setStatusFilter(next);
                                    applyFilters({ status: next, page: '1' });
                                }}
                                className={`px-3 py-1.5 text-xs font-medium rounded-full border transition-colors ${
                                    active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600'
                                }`}
                            >
                                {s.label} <span className="ml-1 opacity-75">{count}</span>
                            </button>
                        );
                    })}
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row gap-3">
                    <div className="flex-1">
                        <SearchBar
                            placeholder="Buscar por cliente ou ID..."
                            initialValue={filters.search ?? ''}
                            onSearch={(q) => applyFilters({ search: q, page: '1' })}
                        />
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(e) => {
                            setStatusFilter(e.target.value);
                            applyFilters({ status: e.target.value, page: '1' });
                        }}
                        className="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        {STATUS_OPTIONS.map((o) => (
                            <option key={o.value} value={o.value}>{o.label}</option>
                        ))}
                    </select>
                </div>

                {/* Table */}
                <DataTable
                    columns={columns}
                    data={orders.data}
                    pagination={orders.meta}
                    emptyMessage="Nenhum pedido encontrado"
                />
            </div>
        </AdminLayout>
    );
}
