import { useState } from 'react';
import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatusBadge from '@/Components/Admin/StatusBadge';
import Modal from '@/Components/Admin/Modal';
import type { OrderStatus, PaymentStatus } from '@/types/admin';
import type { Address } from '@/types/shared';

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
  payment_status: PaymentStatus;
  payment_method?: string | null;
  paid_at?: string | null;
  total: number;
  subtotal: number;
  shipping_cost: number;
  notes?: string;
  created_at: string;
  user: { id: number; name: string; email: string };
  items: ShowOrderItem[];
  shipping_address?: Address | null;
  billing_address?: Address | null;
}

const STATUS_TRANSITIONS: Record<OrderStatus, OrderStatus[]> = {
  pending: ['processing', 'cancelled'],
  processing: ['shipped', 'cancelled'],
  shipped: ['delivered'],
  delivered: [],
  cancelled: [],
};

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'Pendente',
  processing: 'Processando',
  shipped: 'Enviado',
  delivered: 'Entregue',
  cancelled: 'Cancelado',
};

const PAYMENT_LABELS: Record<PaymentStatus, string> = {
  pending: 'Aguardando pagamento',
  paid: 'Pago',
};

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
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

function formatPaymentMethod(method?: string | null): string {
  if (method === 'mock_card') {
    return 'Cartao';
  }

  return method ?? '-';
}

interface OrdersShowProps {
  order: ShowOrder;
}

export default function OrdersShow({ order }: OrdersShowProps) {
  const [confirmModal, setConfirmModal] = useState<{
    open: boolean;
    targetStatus: OrderStatus | null;
  }>({
    open: false,
    targetStatus: null,
  });
  const [updating, setUpdating] = useState(false);

  const availableTransitions = STATUS_TRANSITIONS[order.status] ?? [];
  const shippingAddressLines = formatAddress(order.shipping_address);
  const billingAddressLines = formatAddress(order.billing_address);

  function handleStatusChange(newStatus: OrderStatus) {
    setConfirmModal({ open: true, targetStatus: newStatus });
  }

  function confirmStatusChange() {
    if (!confirmModal.targetStatus) {
      return;
    }

    setUpdating(true);
    router.put(
      `/admin/orders/${order.id}/status`,
      { status: confirmModal.targetStatus },
      {
        onFinish: () => {
          setUpdating(false);
          setConfirmModal({ open: false, targetStatus: null });
        },
      }
    );
  }

  return (
    <AdminLayout title={`Pedido #${String(order.id).padStart(5, '0')}`}>
      <div className="space-y-6 p-6">
        <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <button
              type="button"
              onClick={() => router.visit('/admin/orders')}
              className="mb-2 flex items-center gap-1.5 text-sm text-warm-500 transition-colors hover:text-kintsugi-600"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
              Voltar para Pedidos
            </button>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-warm-700">
                Pedido #{String(order.id).padStart(5, '0')}
              </h1>
              <StatusBadge status={order.status} />
            </div>
            <p className="mt-0.5 text-sm text-warm-500">
              Realizado em {formatDate(order.created_at)}
            </p>
          </div>

          {availableTransitions.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {availableTransitions.map((nextStatus) => (
                <button
                  key={nextStatus}
                  type="button"
                  onClick={() => handleStatusChange(nextStatus)}
                  className={`rounded-lg px-4 py-2 text-sm font-medium transition-colors ${
                    nextStatus === 'cancelled'
                      ? 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                      : 'bg-kintsugi-600 text-white shadow-sm hover:bg-kintsugi-700'
                  }`}
                >
                  Avancar para {STATUS_LABELS[nextStatus]}
                </button>
              ))}
            </div>
          )}
        </div>

        <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <div className="space-y-6 xl:col-span-2">
            <div className="overflow-hidden rounded-xl border border-warm-200 bg-white shadow-xs">
              <div className="border-b border-warm-200 px-6 py-4">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-warm-600">
                  Itens do Pedido
                </h2>
              </div>
              <div className="divide-y divide-warm-200">
                {order.items.map((item) => (
                  <div key={item.id} className="flex items-center justify-between px-6 py-4">
                    <div>
                      <p className="text-sm font-medium text-warm-700">{item.product_name}</p>
                      <p className="mt-0.5 text-xs text-warm-500">
                        {formatCurrency(item.unit_price)} x {item.quantity}
                      </p>
                    </div>
                    <span className="text-sm font-semibold text-warm-700">
                      {formatCurrency(item.total_price)}
                    </span>
                  </div>
                ))}
              </div>
              <div className="space-y-1.5 border-t border-warm-200 bg-warm-50 px-6 py-4">
                <div className="flex justify-between text-sm text-warm-600">
                  <span>Subtotal</span>
                  <span>{formatCurrency(order.subtotal)}</span>
                </div>
                {order.shipping_cost > 0 && (
                  <div className="flex justify-between text-sm text-warm-600">
                    <span>Frete</span>
                    <span>{formatCurrency(order.shipping_cost)}</span>
                  </div>
                )}
                <div className="flex justify-between border-t border-warm-200 pt-2 text-base font-bold text-warm-700">
                  <span>Total</span>
                  <span>{formatCurrency(order.total)}</span>
                </div>
              </div>
            </div>

            {order.notes && (
              <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <div className="flex items-start gap-3">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                  <div>
                    <p className="mb-1 text-sm font-semibold text-amber-800">
                      Observacao do cliente
                    </p>
                    <p className="text-sm text-amber-700">{order.notes}</p>
                  </div>
                </div>
              </div>
            )}
          </div>

          <div className="space-y-6">
            <div className="rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
              <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-600">
                Cliente
              </h2>
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-kintsugi-100 text-sm font-bold text-kintsugi-600">
                  {order.user.name.charAt(0).toUpperCase()}
                </div>
                <div>
                  <p className="text-sm font-medium text-warm-700">{order.user.name}</p>
                  <p className="text-xs text-warm-500">{order.user.email}</p>
                </div>
              </div>
            </div>

            {shippingAddressLines.length > 0 && (
              <div className="rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-600">
                  Endereco de Entrega
                </h2>
                <div className="space-y-0.5 text-sm text-warm-600">
                  {shippingAddressLines.map((line) => (
                    <p key={line}>{line}</p>
                  ))}
                </div>
              </div>
            )}

            {billingAddressLines.length > 0 && (
              <div className="rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-600">
                  Endereco de Cobranca
                </h2>
                <div className="space-y-0.5 text-sm text-warm-600">
                  {billingAddressLines.map((line) => (
                    <p key={line}>{line}</p>
                  ))}
                </div>
              </div>
            )}

            <div className="rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
              <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-600">
                Informacoes
              </h2>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-warm-500">Pedido no</span>
                  <span className="font-mono text-warm-600">
                    #{String(order.id).padStart(5, '0')}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-warm-500">Criado em</span>
                  <span className="text-warm-600">{formatDate(order.created_at)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-warm-500">Status</span>
                  <StatusBadge status={order.status} size="sm" />
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-warm-500">Pagamento</span>
                  <span className="text-right font-medium text-warm-600">
                    {PAYMENT_LABELS[order.payment_status]}
                  </span>
                </div>
                {order.payment_method && (
                  <div className="flex justify-between gap-4">
                    <span className="text-warm-500">Metodo</span>
                    <span className="text-right text-warm-600">
                      {formatPaymentMethod(order.payment_method)}
                    </span>
                  </div>
                )}
                {order.paid_at && (
                  <div className="flex justify-between gap-4">
                    <span className="text-warm-500">Pago em</span>
                    <span className="text-right text-warm-600">{formatDate(order.paid_at)}</span>
                  </div>
                )}
                {order.status === 'pending' && (
                  <div className="rounded-lg border border-kintsugi-100 bg-kintsugi-50 px-3 py-2 text-xs text-warm-600">
                    Pedido confirmado com estoque reservado. O proximo passo operacional e iniciar o
                    processamento.
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-warm-500">Itens</span>
                  <span className="text-warm-600">{order.items.length}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Modal
        isOpen={confirmModal.open}
        onClose={() => setConfirmModal({ open: false, targetStatus: null })}
        title="Confirmar Alteracao de Status"
        onConfirm={confirmStatusChange}
        confirmLabel="Confirmar"
        loading={updating}
      >
        {confirmModal.targetStatus && (
          <p className="text-sm text-warm-600">
            Deseja mover o pedido{' '}
            <span className="font-semibold">#{String(order.id).padStart(5, '0')}</span> para{' '}
            <span className="font-semibold">{STATUS_LABELS[confirmModal.targetStatus]}</span>?
            {confirmModal.targetStatus === 'cancelled' && (
              <span className="mt-2 block text-red-600">Esta acao nao pode ser desfeita.</span>
            )}
          </p>
        )}
      </Modal>
    </AdminLayout>
  );
}
