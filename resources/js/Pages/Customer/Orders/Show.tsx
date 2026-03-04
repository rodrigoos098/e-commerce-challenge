import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import OrderStatusTimeline from '@/Components/Public/OrderStatusTimeline';
import type { OrderShowPageProps, OrderStatus } from '@/types/public';
import type { Address } from '@/types/shared';

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

function formatPrice(value: number): string {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatAddress(address?: Address | null): string[] {
    if (!address) {
        return [];
    }

    return [
        address.name,
        address.street,
        [address.city, address.state].filter(Boolean).join(' - '),
        address.zip_code,
        address.country,
    ].filter((value): value is string => Boolean(value));
}

export default function OrderShow({ order }: OrderShowPageProps) {
    const shippingAddressLines = formatAddress(order.shipping_address);
    const billingAddressLines = formatAddress(order.billing_address);

    return (
        <PublicLayout title={`Pedido #${order.id}`}>
            <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <nav aria-label="Navegacao" className="mb-6 flex items-center gap-2 text-sm text-gray-400">
                    <Link href="/customer/orders" className="transition-colors hover:text-violet-600">Meus Pedidos</Link>
                    <span aria-hidden="true">/</span>
                    <span className="font-medium text-gray-700">Pedido #{order.id}</span>
                </nav>

                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-extrabold text-gray-900 sm:text-3xl">Pedido #{order.id}</h1>
                        <p className="mt-1 text-sm text-gray-500">Realizado em {formatDate(order.created_at)}</p>
                    </div>
                    <span className={`self-start rounded-full border px-3 py-1 text-sm font-semibold sm:self-auto ${STATUS_COLORS[order.status]}`}>
                        {STATUS_LABELS[order.status]}
                    </span>
                </div>

                <div className="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-6 text-base font-bold text-gray-900">Status do Pedido</h2>
                    <OrderStatusTimeline status={order.status} />
                </div>

                <div className="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-5 text-base font-bold text-gray-900">Itens do Pedido</h2>
                    <div className="space-y-4">
                        {order.items.map((item) => (
                            <div key={item.id} className="flex items-center gap-4">
                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100">
                                    <img
                                        src={`https://picsum.photos/seed/${item.product.id}/128/128`}
                                        alt={item.product.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <Link
                                        href={`/products/${item.product.slug}`}
                                        className="line-clamp-1 text-sm font-semibold text-gray-900 transition-colors hover:text-violet-600"
                                    >
                                        {item.product.name}
                                    </Link>
                                    <p className="mt-0.5 text-xs text-gray-400">
                                        {item.quantity} x {formatPrice(item.unit_price)}
                                    </p>
                                </div>
                                <p className="shrink-0 text-sm font-bold text-gray-900">{formatPrice(item.total_price)}</p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6 space-y-2 border-t border-gray-100 pt-5 text-sm">
                        <div className="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{formatPrice(order.subtotal)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600">
                            <span>Impostos</span>
                            <span>{formatPrice(order.tax)}</span>
                        </div>
                        <div className="flex justify-between text-gray-600">
                            <span>Frete</span>
                            <span>{order.shipping_cost === 0 ? 'Gratis' : formatPrice(order.shipping_cost)}</span>
                        </div>
                        <div className="flex justify-between border-t border-gray-100 pt-3 text-base font-bold text-gray-900">
                            <span>Total</span>
                            <span>{formatPrice(order.total)}</span>
                        </div>
                    </div>
                </div>

                <div className="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {shippingAddressLines.length > 0 && (
                        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 className="mb-2 text-sm font-bold text-gray-900">Endereco de Entrega</h3>
                            <div className="space-y-1 text-sm leading-relaxed text-gray-600">
                                {shippingAddressLines.map((line) => (
                                    <p key={line}>{line}</p>
                                ))}
                            </div>
                        </div>
                    )}
                    {billingAddressLines.length > 0 && (
                        <div className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 className="mb-2 text-sm font-bold text-gray-900">Endereco de Cobranca</h3>
                            <div className="space-y-1 text-sm leading-relaxed text-gray-600">
                                {billingAddressLines.map((line) => (
                                    <p key={line}>{line}</p>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {order.notes && (
                    <div className="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 className="mb-2 text-sm font-bold text-gray-900">Observacoes</h3>
                        <p className="text-sm text-gray-600">{order.notes}</p>
                    </div>
                )}

                <div className="flex justify-start">
                    <Link
                        href="/customer/orders"
                        className="inline-flex items-center gap-2 text-sm font-medium text-violet-600 transition-colors hover:text-violet-800"
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
