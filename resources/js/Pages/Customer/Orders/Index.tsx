import React from 'react';
import { Link, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Pagination from '@/Components/Public/Pagination';
import type { OrdersPageProps, OrderStatus } from '@/types/public';
import { formatDate, formatPrice } from '@/utils/format';
import { appRoutes } from '@/utils/routes';

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
  shipped: 'bg-kintsugi-50 text-kintsugi-700 border-kintsugi-100',
  delivered: 'bg-green-50 text-green-700 border-green-100',
  cancelled: 'bg-red-50 text-red-700 border-red-100',
};

function StatusBadge({ status }: { status: OrderStatus }) {
  return (
    <span
      className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${STATUS_COLORS[status]}`}
    >
      {STATUS_LABELS[status]}
    </span>
  );
}

// ——— Page ————————————————————————————————————————————————

export default function OrdersIndex({ orders }: OrdersPageProps) {
  const handlePageChange = (page: number) => {
    router.get(appRoutes.customer.orders.index, { page }, { preserveState: true, replace: true });
  };

  return (
    <PublicLayout
      title="Meus Pedidos"
      description="Acompanhe seus pedidos, pagamentos e entregas em um unico lugar."
    >
      <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">
        <h1 className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700 mb-8">
          Meus Pedidos
        </h1>

        {orders.data.length === 0 ? (
          <div className="text-center py-20">
            <div className="flex h-20 w-20 items-center justify-center rounded-full bg-kintsugi-50 mx-auto mb-5">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-10 w-10 text-kintsugi-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.2}
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
            </div>
            <h2 className="font-display text-xl font-extrabold text-warm-700 mb-2">
              Ainda sem pedidos
            </h2>
            <p className="text-warm-500 mb-6 max-w-xs mx-auto leading-relaxed">
              Cada peça tem uma história. Comece a escrever a sua.
            </p>
            <Link
              href={appRoutes.products.index}
              className="inline-flex items-center gap-2 rounded-full bg-kintsugi-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-kintsugi-600 transition-colors"
            >
              Explorar a coleção
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.data.map((order) => (
              <Link
                key={order.id}
                href={appRoutes.customer.orders.show(order.id)}
                className="block rounded-2xl bg-white border border-warm-200 shadow-sm hover:shadow-md hover:border-kintsugi-200 transition-all duration-200 p-5"
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div className="flex items-center gap-4">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-kintsugi-50">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-5 w-5 text-kintsugi-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={1.8}
                        aria-hidden="true"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                      </svg>
                    </div>
                    <div>
                      <p className="text-sm font-bold text-warm-700">Pedido #{order.id}</p>
                      <p className="text-xs text-warm-400">{formatDate(order.created_at)}</p>
                    </div>
                  </div>

                  <div className="flex items-center gap-4 pl-14 sm:pl-0">
                    <StatusBadge status={order.status} />
                    <p className="text-sm font-bold text-warm-700">{formatPrice(order.total)}</p>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      className="h-4 w-4 text-warm-400"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2}
                      aria-hidden="true"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </div>
                </div>
              </Link>
            ))}

            <Pagination meta={orders.meta} onPageChange={handlePageChange} />
          </div>
        )}
      </div>
    </PublicLayout>
  );
}
