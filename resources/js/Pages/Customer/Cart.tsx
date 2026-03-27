import React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Modal from '@/Components/Shared/Modal';
import PublicLayout from '@/Layouts/PublicLayout';
import CartItemComponent from '@/Components/Public/CartItem';
import type { CartPageProps } from '@/types/public';
import { formatPrice } from '@/utils/format';
import { appRoutes } from '@/utils/routes';

export default function Cart({ cart }: CartPageProps) {
  const { auth } = usePage<{ auth?: { user?: { id: number } | null } }>().props;
  const isAuthenticated = Boolean(auth?.user);
  const [clearCartModalOpen, setClearCartModalOpen] = React.useState(false);
  const [clearingCart, setClearingCart] = React.useState(false);

  const handleClearCart = () => {
    setClearingCart(true);

    router.delete(appRoutes.cart.index, {
      onSuccess: () => toast.success('Sacola esvaziada.'),
      onError: () => toast.error('Não foi possível esvaziar a sacola.'),
      onFinish: () => {
        setClearingCart(false);
        setClearCartModalOpen(false);
      },
    });
  };

  if (cart.items.length === 0) {
    return (
      <PublicLayout title="Carrinho">
        <div className="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
          <div className="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-kintsugi-50">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-12 w-12 text-kintsugi-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={1.2}
              aria-hidden="true"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
          </div>
          <h1 className="mb-2 font-display text-2xl font-extrabold text-warm-700">
            Sua sacola está vazia
          </h1>
          <p className="mx-auto mb-8 max-w-xs text-warm-500 leading-relaxed">
            Cada peça tem uma história. Encontre a que vai contar a sua.
          </p>
          <Link
            href={appRoutes.products.index}
            className="inline-flex items-center gap-2 rounded-full bg-kintsugi-500 px-8 py-3 text-sm font-bold text-white shadow-lg transition-colors hover:bg-kintsugi-600"
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
            Explorar Coleção
          </Link>
        </div>
      </PublicLayout>
    );
  }

  return (
    <PublicLayout title="Carrinho">
      <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
          <h1 className="font-display text-2xl font-extrabold text-warm-700 sm:text-3xl">
            Sua Sacola
            <span className="ml-2 text-base font-normal text-warm-400">
              ({cart.item_count} {cart.item_count === 1 ? 'item' : 'itens'})
            </span>
          </h1>
          <button
            type="button"
            onClick={() => setClearCartModalOpen(true)}
            className="shrink-0 text-sm font-medium text-red-500 transition-colors hover:text-red-700"
          >
            Limpar carrinho
          </button>
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <div className="lg:col-span-2">
            <div className="rounded-2xl border border-warm-200 bg-white px-6 shadow-sm">
              {cart.items.map((item) => (
                <CartItemComponent key={item.id} item={item} />
              ))}
            </div>

            <div className="mt-4">
              <Link
                href={appRoutes.products.index}
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
                Continuar comprando
              </Link>
            </div>
          </div>

          <div className="lg:col-span-1">
            <div className="sticky top-24 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
              <h2 className="mb-5 text-lg font-bold text-warm-700">Resumo do pedido</h2>

              <div className="space-y-3 text-sm">
                <div className="flex justify-between text-warm-600">
                  <span>Subtotal</span>
                  <span>{formatPrice(cart.subtotal)}</span>
                </div>
                <div className="flex justify-between text-warm-600">
                  <span>Impostos</span>
                  <span>{formatPrice(cart.tax)}</span>
                </div>
                <div className="flex justify-between text-warm-600">
                  <span>Frete</span>
                  <span className={cart.shipping_is_free ? 'font-medium text-green-600' : ''}>
                    {cart.shipping_is_free ? 'Grátis' : formatPrice(cart.shipping_cost)}
                  </span>
                </div>
                <div className="rounded-xl bg-warm-50 px-3 py-2 text-xs text-warm-500">
                  <p className="font-semibold text-warm-600">{cart.shipping_rule_label}</p>
                  <p className="mt-1">{cart.shipping_rule_description}</p>
                </div>
                <div className="flex justify-between border-t border-warm-200 pt-3 text-base font-bold text-warm-700">
                  <span>Total</span>
                  <span>{formatPrice(cart.total)}</span>
                </div>
              </div>

              <Link
                href={isAuthenticated ? appRoutes.customer.checkout : appRoutes.auth.login}
                className="mt-6 block w-full rounded-full bg-kintsugi-500 py-3.5 text-center text-sm font-bold text-white shadow-sm transition-all hover:bg-kintsugi-600 active:scale-95"
              >
                {isAuthenticated ? 'Finalizar compra' : 'Entrar para finalizar'}
              </Link>

              <div className="mt-4 flex items-center justify-center gap-1.5 text-xs text-warm-400">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-3.5 w-3.5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                  />
                </svg>
                <span>Compra segura com SSL</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Modal
        isOpen={clearCartModalOpen}
        onClose={() => setClearCartModalOpen(false)}
        title="Limpar sacola"
        onConfirm={handleClearCart}
        confirmLabel="Limpar agora"
        confirmDestructive
        loading={clearingCart}
      >
        <p className="text-sm leading-relaxed text-warm-600">
          Essa ação remove todos os itens da sua sacola atual.
        </p>
      </Modal>
    </PublicLayout>
  );
}
