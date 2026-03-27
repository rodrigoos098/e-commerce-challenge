import React from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Modal from '@/Components/Shared/Modal';
import PublicLayout from '@/Layouts/PublicLayout';
import OrderStatusTimeline from '@/Components/Public/OrderStatusTimeline';
import type { OrderShowPageProps, OrderStatus, PaymentStatus } from '@/types/public';
import type { Address } from '@/types/shared';
import {
  getProductImageSrc,
  handleProductImageError,
  ProductImageFallback,
} from '@/utils/productImage';
import { formatDateTime, formatPrice } from '@/utils/format';
import { appRoutes } from '@/utils/routes';

const STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'Confirmado',
  processing: 'Processando',
  shipped: 'Enviado',
  delivered: 'Entregue',
  cancelled: 'Cancelado',
};

const STATUS_COLORS: Record<OrderStatus, string> = {
  pending: 'bg-amber-50 text-amber-700 border-amber-100',
  processing: 'bg-blue-50 text-blue-700 border-blue-100',
  shipped: 'bg-kintsugi-50 text-kintsugi-700 border-kintsugi-100',
  delivered: 'bg-green-50 text-green-700 border-green-100',
  cancelled: 'bg-red-50 text-red-700 border-red-100',
};

const PAYMENT_LABELS: Record<PaymentStatus, string> = {
  pending: 'Aguardando pagamento',
  paid: 'Pago',
};

const PAYMENT_COLORS: Record<PaymentStatus, string> = {
  pending: 'border-amber-100 bg-amber-50 text-amber-700',
  paid: 'border-green-100 bg-green-50 text-green-700',
};

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

export default function OrderShow({ order }: OrderShowPageProps) {
  const shippingAddressLines = formatAddress(order.shipping_address);
  const billingAddressLines = formatAddress(order.billing_address);
  const [cancelModalOpen, setCancelModalOpen] = React.useState(false);
  const [cancelling, setCancelling] = React.useState(false);

  const handleCancelOrder = () => {
    setCancelling(true);

    router.put(
      appRoutes.customer.orders.cancel(order.id),
      {},
      {
        onSuccess: () => toast.success('Pedido cancelado com sucesso!'),
        onError: () => toast.error('Nao foi possivel cancelar o pedido.'),
        onFinish: () => {
          setCancelling(false);
          setCancelModalOpen(false);
        },
      }
    );
  };

  return (
    <PublicLayout title={`Pedido #${order.id}`}>
      <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <nav aria-label="Navegacao" className="mb-6 flex items-center gap-2 text-sm text-warm-400">
          <Link
            href={appRoutes.customer.orders.index}
            className="transition-colors hover:text-kintsugi-600"
          >
            Meus Pedidos
          </Link>
          <span aria-hidden="true">/</span>
          <span className="font-medium text-warm-600">Pedido #{order.id}</span>
        </nav>

        <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-extrabold text-warm-700 sm:text-3xl">
              Pedido #{order.id}
            </h1>
            <p className="mt-1 text-sm text-warm-500">
              Realizado em {formatDateTime(order.created_at)}
            </p>
          </div>
          <div className="flex flex-col items-start gap-3 sm:items-end">
            <span
              className={`self-start rounded-full border px-3 py-1 text-sm font-semibold sm:self-auto ${STATUS_COLORS[order.status]}`}
            >
              {STATUS_LABELS[order.status]}
            </span>
            {order.can_cancel && (
              <button
                type="button"
                onClick={() => setCancelModalOpen(true)}
                className="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition-colors hover:bg-red-100"
              >
                Cancelar pedido
              </button>
            )}
          </div>
        </div>

        <div className="mb-6 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
          <h2 className="mb-6 text-base font-bold text-warm-700">Status do Pedido</h2>
          {order.status === 'pending' && (
            <div className="mb-5 rounded-2xl border border-kintsugi-100 bg-kintsugi-50 px-4 py-3 text-sm text-warm-600">
              Seu pedido foi confirmado com estoque reservado. Em breve nossa equipe iniciara a
              separacao e voce vera as proximas etapas nesta timeline.
            </div>
          )}
          {order.status === 'processing' && (
            <div className="mb-5 rounded-2xl border border-kintsugi-100 bg-kintsugi-50 px-4 py-3 text-sm text-warm-600">
              Estamos preparando o seu pedido para envio. Voce pode acompanhar as proximas
              atualizacoes nesta timeline.
            </div>
          )}
          <OrderStatusTimeline status={order.status} />
        </div>

        <div className="mb-6 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-base font-bold text-warm-700">Pagamento</h2>
          <div className="flex flex-wrap items-center gap-3">
            <span
              className={`rounded-full border px-3 py-1 text-sm font-semibold ${PAYMENT_COLORS[order.payment_status]}`}
            >
              {PAYMENT_LABELS[order.payment_status]}
            </span>
            {order.payment_method && (
              <span className="rounded-full border border-warm-200 bg-warm-50 px-3 py-1 text-sm font-medium text-warm-600">
                Metodo: {formatPaymentMethod(order.payment_method)}
              </span>
            )}
          </div>
          {order.paid_at && (
            <p className="mt-3 text-sm text-warm-500">
              Registrado em {formatDateTime(order.paid_at)}
            </p>
          )}
        </div>

        <div className="mb-6 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
          <h2 className="mb-5 text-base font-bold text-warm-700">Itens do Pedido</h2>
          <div className="space-y-4">
            {order.items.map((item) => (
              <div key={item.id} className="flex items-center gap-4">
                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-warm-200 bg-warm-50">
                  <img
                    src={getProductImageSrc(item.product)}
                    alt={item.product.name}
                    className="h-full w-full object-cover"
                    loading="lazy"
                    onError={handleProductImageError}
                  />
                  <ProductImageFallback />
                </div>
                <div className="min-w-0 flex-1">
                  <Link
                    href={appRoutes.products.show(item.product.slug)}
                    className="line-clamp-1 text-sm font-semibold text-warm-700 transition-colors hover:text-kintsugi-600"
                  >
                    {item.product.name}
                  </Link>
                  <p className="mt-0.5 text-xs text-warm-400">
                    {item.quantity} x {formatPrice(item.unit_price)}
                  </p>
                </div>
                <p className="shrink-0 text-sm font-bold text-warm-700">
                  {formatPrice(item.total_price)}
                </p>
              </div>
            ))}
          </div>

          <div className="mt-6 space-y-2 border-t border-warm-200 pt-5 text-sm">
            <div className="flex justify-between text-warm-600">
              <span>Subtotal</span>
              <span>{formatPrice(order.subtotal)}</span>
            </div>
            <div className="flex justify-between text-warm-600">
              <span>Impostos</span>
              <span>{formatPrice(order.tax)}</span>
            </div>
            <div className="flex justify-between text-warm-600">
              <span>Frete</span>
              <span>{order.shipping_cost === 0 ? 'Gratis' : formatPrice(order.shipping_cost)}</span>
            </div>
            <div className="flex justify-between border-t border-warm-200 pt-3 text-base font-bold text-warm-700">
              <span>Total</span>
              <span>{formatPrice(order.total)}</span>
            </div>
          </div>
        </div>

        <div className="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
          {shippingAddressLines.length > 0 && (
            <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
              <h3 className="mb-2 text-sm font-bold text-warm-700">Endereco de Entrega</h3>
              <div className="space-y-1 text-sm leading-relaxed text-warm-600">
                {shippingAddressLines.map((line) => (
                  <p key={line}>{line}</p>
                ))}
              </div>
            </div>
          )}
          {billingAddressLines.length > 0 && (
            <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
              <h3 className="mb-2 text-sm font-bold text-warm-700">Endereco de Cobranca</h3>
              <div className="space-y-1 text-sm leading-relaxed text-warm-600">
                {billingAddressLines.map((line) => (
                  <p key={line}>{line}</p>
                ))}
              </div>
            </div>
          )}
        </div>

        {order.notes && (
          <div className="mb-6 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
            <h3 className="mb-2 text-sm font-bold text-warm-700">Observacoes</h3>
            <p className="text-sm text-warm-600">{order.notes}</p>
          </div>
        )}

        <div className="flex justify-start">
          <Link
            href={appRoutes.customer.orders.index}
            className="inline-flex items-center gap-2 text-sm font-medium text-kintsugi-500 transition-colors hover:text-kintsugi-600"
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
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar para meus pedidos
          </Link>
        </div>

        <Modal
          isOpen={cancelModalOpen}
          onClose={() => setCancelModalOpen(false)}
          title="Cancelar pedido"
          onConfirm={handleCancelOrder}
          confirmLabel="Cancelar pedido"
          confirmDestructive
          loading={cancelling}
        >
          <p className="text-sm leading-relaxed text-warm-600">
            O pedido #{order.id} sera cancelado e nao podera voltar para o fluxo atual.
          </p>
        </Modal>
      </div>
    </PublicLayout>
  );
}
