import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import OrderStatusTimeline from '@/Components/Public/OrderStatusTimeline';
import type { OrderShowPageProps, Order, OrderStatus } from '@/types/public';

// ——— Mock ——————————————————————————————————————————————————

const MOCK_ORDER: Order = {
    id: 102,
    user_id: 1,
    status: 'shipped',
    total: 1540.67,
    subtotal: 1399.7,
    tax: 125.97,
    shipping_cost: 15.0,
    shipping_address: 'Rua Exemplo, 123 — São Paulo, SP — 01310-100',
    billing_address: 'Rua Exemplo, 123 — São Paulo, SP — 01310-100',
    notes: 'Entregar no período da tarde.',
    items: [
        {
            id: 1,
            product: {
                id: 1,
                name: 'Fone de Ouvido Bluetooth Premium',
                slug: 'fone-bluetooth',
                description: '',
                price: 299.9,
                quantity: 50,
                min_quantity: 5,
                active: true,
                category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null },
                tags: [],
                created_at: '',
                updated_at: '',
            },
            quantity: 2,
            unit_price: 299.9,
            total_price: 599.8,
        },
        {
            id: 2,
            product: {
                id: 4,
                name: 'Smart Watch Series X',
                slug: 'smart-watch',
                description: '',
                price: 799.9,
                quantity: 15,
                min_quantity: 5,
                active: true,
                category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null },
                tags: [],
                created_at: '',
                updated_at: '',
            },
            quantity: 1,
            unit_price: 799.9,
            total_price: 799.9,
        },
    ],
    created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
};

// ——— Helpers ————————————————————————————————————————————————

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

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// ——— Page ————————————————————————————————————————————————

export default function OrderShow({ order }: Partial<OrderShowPageProps>) {
    const o = order ?? MOCK_ORDER;

    return (
        <PublicLayout title={`Pedido #${o.id}`}>
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">
                {/* Breadcrumb */}
                <nav aria-label="Navegação" className="mb-6 flex items-center gap-2 text-sm text-gray-400">
                    <Link href="/customer/orders" className="hover:text-violet-600 transition-colors">Meus Pedidos</Link>
                    <span aria-hidden="true">/</span>
                    <span className="text-gray-700 font-medium">Pedido #{o.id}</span>
                </nav>

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900">Pedido #{o.id}</h1>
                        <p className="mt-1 text-sm text-gray-500">Realizado em {formatDate(o.created_at)}</p>
                    </div>
                    <span className={`self-start sm:self-auto inline-flex items-center rounded-full border px-3 py-1 text-sm font-semibold ${STATUS_COLORS[o.status]}`}>
                        {STATUS_LABELS[o.status]}
                    </span>
                </div>

                {/* Timeline */}
                <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 mb-6">
                    <h2 className="text-base font-bold text-gray-900 mb-6">Status do Pedido</h2>
                    <OrderStatusTimeline status={o.status} />
                </div>

                {/* Items */}
                <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 mb-6">
                    <h2 className="text-base font-bold text-gray-900 mb-5">Itens do Pedido</h2>
                    <div className="space-y-4">
                        {o.items.map((item) => (
                            <div key={item.id} className="flex items-center gap-4">
                                <div className="h-16 w-16 rounded-xl overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                                    <img
                                        src={`https://picsum.photos/seed/${item.product.id}/128/128`}
                                        alt={item.product.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <Link
                                        href={`/products/${item.product.slug}`}
                                        className="text-sm font-semibold text-gray-900 hover:text-violet-600 transition-colors line-clamp-1"
                                    >
                                        {item.product.name}
                                    </Link>
                                    <p className="text-xs text-gray-400 mt-0.5">
                                        {item.quantity} × {formatPrice(item.unit_price)}
                                    </p>
                                </div>
                                <p className="text-sm font-bold text-gray-900 shrink-0">{formatPrice(item.total_price)}</p>
                            </div>
                        ))}
                    </div>

                    {/* Price breakdown */}
                    <div className="mt-6 border-t border-gray-100 pt-5 space-y-2 text-sm">
                        <div className="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{formatPrice(o.subtotal)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600">
                            <span>Impostos</span>
                            <span>{formatPrice(o.tax)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600">
                            <span>Frete</span>
                            <span>{o.shipping_cost === 0 ? 'Grátis' : formatPrice(o.shipping_cost)}</span>
                        </div>
                        <div className="flex justify-between font-bold text-base text-gray-900 pt-3 border-t border-gray-100">
                            <span>Total</span>
                            <span>{formatPrice(o.total)}</span>
                        </div>
                    </div>
                </div>

                {/* Addresses */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    {o.shipping_address && (
                        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
                            <h3 className="text-sm font-bold text-gray-900 mb-2">Endereço de Entrega</h3>
                            <p className="text-sm text-gray-600 leading-relaxed">{o.shipping_address}</p>
                        </div>
                    )}
                    {o.billing_address && (
                        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
                            <h3 className="text-sm font-bold text-gray-900 mb-2">Endereço de Cobrança</h3>
                            <p className="text-sm text-gray-600 leading-relaxed">{o.billing_address}</p>
                        </div>
                    )}
                </div>

                {/* Notes */}
                {o.notes && (
                    <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 mb-6">
                        <h3 className="text-sm font-bold text-gray-900 mb-2">Observações</h3>
                        <p className="text-sm text-gray-600">{o.notes}</p>
                    </div>
                )}

                <div className="flex justify-start">
                    <Link
                        href="/customer/orders"
                        className="inline-flex items-center gap-2 text-sm text-violet-600 hover:text-violet-800 font-medium transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Voltar para meus pedidos
                    </Link>
                </div>
            </div>
        </PublicLayout>
    );
}
