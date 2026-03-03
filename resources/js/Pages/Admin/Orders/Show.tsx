import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/Admin/StatusBadge';
import Modal from '@/Components/Admin/Modal';
import type { OrderStatus } from '@/types/admin';

// — Local types matching what the backend sends for this view ——————————————————
interface ShowOrderItem {
    id: number;
    product_name: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

interface ShowOrder {
    id: number;
    status: OrderStatus;
    total: number;
    subtotal: number;
    shipping_cost: number;
    notes?: string;
    created_at: string;
    user: { id: number; name: string; email: string };
    items: ShowOrderItem[];
    shipping_address?: string;
}

// — Mock data ——————————————————————————————————
const MOCK_ORDER: ShowOrder = {
    id: 3,
    status: 'shipped',
    total: 789.00,
    subtotal: 719.00,
    shipping_cost: 70.00,
    notes: 'Presente — incluir embalagem especial.',
    created_at: '2025-01-08T08:00:00Z',
    user: { id: 3, name: 'Carla Dias', email: 'carla@example.com' },
    items: [
        { id: 1, product_name: 'Teclado Mecânico RGB',  quantity: 1, unit_price: 649.00, total_price: 649.00 },
        { id: 2, product_name: 'Mouse Pad XL',          quantity: 2, unit_price: 35.00,  total_price: 70.00  },
    ],
    shipping_address: JSON.stringify({
        street: 'Rua das Flores, 123',
        neighborhood: 'Jardins',
        city: 'São Paulo',
        state: 'SP',
        zip: '01425-000',
    }),
};

// — Config ——————————————————————
const STATUS_TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
    pending:    ['processing', 'cancelled'],
    processing: ['shipped', 'cancelled'],
    shipped:    ['delivered'],
    delivered:  [],
    cancelled:  [],
};

const STATUS_LABELS: Record<OrderStatus, string> = {
    pending:    'Pendente',
    processing: 'Processando',
    shipped:    'Enviado',
    delivered:  'Entregue',
    cancelled:  'Cancelado',
};

// — Helpers ——————————————————————
function formatDate(iso: string) {
    return new Date(iso).toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatCurrency(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function parseAddress(raw?: string): Record<string, string> {
    try {
        return raw ? (JSON.parse(raw) as Record<string, string>) : {};
    } catch {
        return {};
    }
}

// — Props ——————————————————————
interface OrdersShowProps {
    order?: ShowOrder;
}

// — Component ——————————————————————
export default function OrdersShow({ order = MOCK_ORDER }: OrdersShowProps) {
    const [confirmModal, setConfirmModal] = useState<{ open: boolean; targetStatus: OrderStatus | null }>({
        open: false, targetStatus: null,
    });
    const [updating, setUpdating] = useState(false);

    const availableTransitions = STATUS_TRANSITIONS[order.status] ?? [];
    const shippingAddr = parseAddress(order.shipping_address);

    function handleStatusChange(newStatus: OrderStatus) {
        setConfirmModal({ open: true, targetStatus: newStatus });
    }

    function confirmStatusChange() {
        if (!confirmModal.targetStatus) { return; }
        setUpdating(true);
        router.put(`/admin/orders/${order.id}/status`, { status: confirmModal.targetStatus }, {
            onFinish: () => {
                setUpdating(false);
                setConfirmModal({ open: false, targetStatus: null });
            },
        });
    }

    return (
        <AdminLayout title={`Pedido #${String(order.id).padStart(5, '0')}`}>
            <div className="p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <button
                            type="button"
                            onClick={() => router.visit('/admin/orders')}
                            className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 mb-2 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Voltar para Pedidos
                        </button>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-gray-900">
                                Pedido #{String(order.id).padStart(5, '0')}
                            </h1>
                            <StatusBadge status={order.status} />
                        </div>
                        <p className="text-sm text-gray-500 mt-0.5">Realizado em {formatDate(order.created_at)}</p>
                    </div>

                    {/* Status action buttons */}
                    {availableTransitions.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {availableTransitions.map((nextStatus) => (
                                <button
                                    key={nextStatus}
                                    type="button"
                                    onClick={() => handleStatusChange(nextStatus)}
                                    className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
                                        nextStatus === 'cancelled'
                                            ? 'text-red-700 bg-red-50 hover:bg-red-100 border border-red-200'
                                            : 'text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm'
                                    }`}
                                >
                                    → {STATUS_LABELS[nextStatus]}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    {/* Left — items + notes */}
                    <div className="xl:col-span-2 space-y-6">
                        {/* Items table */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-100">
                                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">Itens do Pedido</h2>
                            </div>
                            <div className="divide-y divide-gray-100">
                                {order.items.map((item) => (
                                    <div key={item.id} className="flex items-center justify-between px-6 py-4">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">{item.product_name}</p>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                {formatCurrency(item.unit_price)} × {item.quantity}
                                            </p>
                                        </div>
                                        <span className="text-sm font-semibold text-gray-900">
                                            {formatCurrency(item.total_price)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            {/* Totals */}
                            <div className="px-6 py-4 bg-gray-50 border-t border-gray-100 space-y-1.5">
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>Subtotal</span>
                                    <span>{formatCurrency(order.subtotal)}</span>
                                </div>
                                {order.shipping_cost > 0 && (
                                    <div className="flex justify-between text-sm text-gray-600">
                                        <span>Frete</span>
                                        <span>{formatCurrency(order.shipping_cost)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
                                    <span>Total</span>
                                    <span>{formatCurrency(order.total)}</span>
                                </div>
                            </div>
                        </div>

                        {/* Notes */}
                        {order.notes && (
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                <div className="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <div>
                                        <p className="text-sm font-semibold text-amber-800 mb-1">Observação do cliente</p>
                                        <p className="text-sm text-amber-700">{order.notes}</p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right — customer + address + metadata */}
                    <div className="space-y-6">
                        {/* Customer */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Cliente</h2>
                            <div className="flex items-center gap-3">
                                <div className="h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
                                    {order.user.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{order.user.name}</p>
                                    <p className="text-xs text-gray-500">{order.user.email}</p>
                                </div>
                            </div>
                        </div>

                        {/* Shipping address */}
                        {Object.keys(shippingAddr).length > 0 && (
                            <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Endereço de Entrega</h2>
                                <div className="text-sm text-gray-600 space-y-0.5">
                                    {shippingAddr.street        && <p>{shippingAddr.street}</p>}
                                    {shippingAddr.neighborhood  && <p>{shippingAddr.neighborhood}</p>}
                                    {(shippingAddr.city || shippingAddr.state) && (
                                        <p>{[shippingAddr.city, shippingAddr.state].filter(Boolean).join(' — ')}</p>
                                    )}
                                    {shippingAddr.zip && (
                                        <p className="font-mono text-xs mt-1">{shippingAddr.zip}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Metadata */}
                        <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                            <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Informações</h2>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Pedido nº</span>
                                    <span className="font-mono text-gray-700">#{String(order.id).padStart(5, '0')}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Criado em</span>
                                    <span className="text-gray-700">{formatDate(order.created_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Status</span>
                                    <StatusBadge status={order.status} size="sm" />
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Itens</span>
                                    <span className="text-gray-700">{order.items.length}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Confirm modal */}
            <Modal
                isOpen={confirmModal.open}
                onClose={() => setConfirmModal({ open: false, targetStatus: null })}
                title="Confirmar Alteração de Status"
                onConfirm={confirmStatusChange}
                confirmLabel="Confirmar"
                loading={updating}
            >
                {confirmModal.targetStatus && (
                    <p className="text-sm text-gray-600">
                        Deseja mover o pedido{' '}
                        <span className="font-semibold">#{String(order.id).padStart(5, '0')}</span> para{' '}
                        <span className="font-semibold">{STATUS_LABELS[confirmModal.targetStatus]}</span>?
                        {confirmModal.targetStatus === 'cancelled' && (
                            <span className="block mt-2 text-red-600">Esta ação não pode ser desfeita.</span>
                        )}
                    </p>
                )}
            </Modal>
        </AdminLayout>
    );
}
