import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Pagination from '@/Components/Public/Pagination';
import type { OrdersPageProps, Order, OrderStatus, PaginatedResponse } from '@/types/public';

// ——— Mock ——————————————————————————————————————————————————

const MOCK_ORDERS: Order[] = Array.from({ length: 5 }, (_, i) => ({
    id: i + 100,
    user_id: 1,
    status: (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as OrderStatus[])[i],
    total: [320.0, 1540.0, 89.9, 2199.9, 499.9][i],
    subtotal: [290.0, 1399.7, 79.9, 2000.0, 450.0][i],
    tax: [26.1, 125.97, 7.19, 180.0, 40.5][i],
    shipping_cost: [3.9, 14.33, 2.81, 19.9, 9.4][i],
    items: [],
    created_at: new Date(Date.now() - i * 7 * 24 * 60 * 60 * 1000).toISOString(),
}));

const MOCK_PAGINATED: PaginatedResponse<Order> = {
    data: MOCK_ORDERS,
    meta: { current_page: 1, per_page: 15, total: 5, last_page: 1 },
    links: { first: null, last: null, prev: null, next: null },
};

// ——— Status badge ————————————————————————————————————————

const STATUS_LABELS: Record<OrderStatus, string> = {
    pending: 'Aguardando',
    processing: 'Processando',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
};

const STATUS_COLORS: Record<OrderStatus, string> = {
    pending: 'bg-amber-50 text-amber-700 border-amber-100',
    processing: 'bg-blue-50 text-blue-700 border-blue-100',
    shipped: 'bg-indigo-50 text-indigo-700 border-indigo-100',
    delivered: 'bg-green-50 text-green-700 border-green-100',
    cancelled: 'bg-red-50 text-red-700 border-red-100',
};

function StatusBadge({ status }: { status: OrderStatus }) {
    return (
        <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${STATUS_COLORS[status]}`}>
            {STATUS_LABELS[status]}
        </span>
    );
}

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ——— Page ————————————————————————————————————————————————

export default function OrdersIndex({ orders }: Partial<OrdersPageProps>) {
    const data = orders ?? MOCK_PAGINATED;

    const handlePageChange = (page: number) => {
        window.location.href = `/customer/orders?page=${page}`;
    };

    return (
        <PublicLayout title="Meus Pedidos">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">
                <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-8">Meus Pedidos</h1>

                {data.data.length === 0 ? (
                    <div className="text-center py-20">
                        <div className="flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 mx-auto mb-5">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.2} aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h2 className="text-lg font-bold text-gray-700 mb-2">Nenhum pedido ainda</h2>
                        <p className="text-gray-500 mb-6">Quando você fizer um pedido, ele aparecerá aqui.</p>
                        <Link
                            href="/products"
                            className="inline-flex items-center gap-2 rounded-2xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 transition-colors"
                        >
                            Ir para a loja
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {data.data.map((order) => (
                            <Link
                                key={order.id}
                                href={`/customer/orders/${order.id}`}
                                className="block rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-violet-200 transition-all duration-200 p-5"
                            >
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div className="flex items-center gap-4">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8} aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-gray-900">Pedido #{order.id}</p>
                                            <p className="text-xs text-gray-400">{formatDate(order.created_at)}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 ml-14 sm:ml-0">
                                        <StatusBadge status={order.status} />
                                        <p className="text-sm font-bold text-gray-900">{formatPrice(order.total)}</p>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>
                        ))}

                        <Pagination meta={data.meta} onPageChange={handlePageChange} />
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
