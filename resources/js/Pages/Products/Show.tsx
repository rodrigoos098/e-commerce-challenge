import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import PublicLayout from '@/Layouts/PublicLayout';
import QuantitySelector from '@/Components/Public/QuantitySelector';
import ProductGrid from '@/Components/Public/ProductGrid';
import Spinner from '@/Components/Shared/Spinner';
import { useCartSound } from '@/hooks/useCartSound';
import { formatPrice } from '@/utils/format';
import { appRoutes } from '@/utils/routes';
import {
  getProductImageSrc,
  handleProductImageError,
  ProductImageFallback,
} from '@/utils/productImage';
import type { ProductShowPageProps } from '@/types/public';

function StockIndicator({ quantity, minQuantity }: { quantity: number; minQuantity: number }) {
  if (quantity === 0) {
    return (
      <div className="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-600 border border-red-100">
        <span className="h-1.5 w-1.5 rounded-full bg-red-500" aria-hidden="true" />
        Esgotado
      </div>
    );
  }
  if (quantity <= minQuantity) {
    return (
      <div className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-600 border border-amber-100">
        <span className="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true" />
        Últimas {quantity} unidades
      </div>
    );
  }
  return (
    <div className="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-600 border border-green-100">
      <span className="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true" />
      Em estoque ({quantity} disponíveis)
    </div>
  );
}

// ——— Page ————————————————————————————————————————————————

export default function ProductShow({ product, related_products }: ProductShowPageProps) {
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const { playCartSound } = useCartSound();

  const isOutOfStock = product.quantity === 0;

  const handleAddToCart = () => {
    if (isOutOfStock) {
      return;
    }
    setAdding(true);
    router.post(
      appRoutes.cart.items,
      { product_id: product.id, quantity },
      {
        preserveScroll: true,
        onSuccess: () => {
          playCartSound();
          toast.success(`"${product.name}" adicionado ao carrinho!`);
          setAdding(false);
        },
        onError: () => {
          toast.error('Erro ao adicionar ao carrinho.');
          setAdding(false);
        },
      }
    );
  };

  return (
    <PublicLayout
      title={product.name}
      description={product.description}
      image={getProductImageSrc(product)}
    >
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        {/* Breadcrumb */}
        <nav aria-label="Navegação" className="mb-8 flex items-center gap-2 text-sm text-warm-400">
          <Link href={appRoutes.home} className="hover:text-kintsugi-600 transition-colors">
            Início
          </Link>
          <span aria-hidden="true">/</span>
          <Link
            href={appRoutes.products.index}
            className="hover:text-kintsugi-600 transition-colors"
          >
            Produtos
          </Link>
          {product.category && (
            <>
              <span aria-hidden="true">/</span>
              <Link
                href={`${appRoutes.products.index}?category_id=${product.category.id}`}
                className="hover:text-kintsugi-600 transition-colors"
              >
                {product.category.name}
              </Link>
            </>
          )}
          <span aria-hidden="true">/</span>
          <span className="text-warm-600 font-medium line-clamp-1">{product.name}</span>
        </nav>

        {/* Product detail */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
          {/* Images */}
          <div>
            <div className="relative overflow-hidden rounded-2xl bg-warm-50 border border-warm-200 aspect-square">
              <img
                src={getProductImageSrc(product)}
                alt={product.name}
                className="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                onError={handleProductImageError}
              />
              <ProductImageFallback />
            </div>
          </div>

          {/* Info */}
          <div className="flex flex-col">
            {/* Category & Tags */}
            <div className="flex flex-wrap items-center gap-2 mb-4">
              {product.category && (
                <Link
                  href={`${appRoutes.products.index}?category_id=${product.category.id}`}
                  className="rounded-full bg-kintsugi-100 px-3 py-1 text-xs font-semibold text-kintsugi-700 hover:bg-kintsugi-200 transition-colors"
                >
                  {product.category.name}
                </Link>
              )}
              {product.tags.map((tag) => (
                <span
                  key={tag.id}
                  className="rounded-full bg-warm-100 px-3 py-1 text-xs font-medium text-warm-600"
                >
                  #{tag.name}
                </span>
              ))}
            </div>

            {/* Name */}
            <h1 className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700 leading-tight mb-4">
              {product.name}
            </h1>

            {/* Price */}
            <div className="mb-6">
              <p className="font-display text-4xl font-extrabold text-warm-700">
                {formatPrice(product.price)}
              </p>
            </div>

            {/* Stock indicator */}
            <div className="mb-6">
              <StockIndicator quantity={product.quantity} minQuantity={product.min_quantity} />
            </div>

            {/* Description */}
            <p className="text-base text-warm-600 leading-relaxed mb-8">{product.description}</p>

            {/* Quantity + Add to cart */}
            {!isOutOfStock && (
              <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
                <div>
                  <label className="sr-only">Quantidade</label>
                  <QuantitySelector
                    value={quantity}
                    onChange={setQuantity}
                    max={product.quantity}
                  />
                </div>
                <button
                  type="button"
                  onClick={handleAddToCart}
                  disabled={adding}
                  className="flex-1 sm:flex-none flex items-center justify-center gap-2 rounded-2xl bg-kintsugi-500 px-8 py-3.5 text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {adding ? (
                    <>
                      <Spinner />
                      Adicionando...
                    </>
                  ) : (
                    <>
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2.5}
                        aria-hidden="true"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                      </svg>
                      Adicionar ao Carrinho
                    </>
                  )}
                </button>
              </div>
            )}

            {isOutOfStock && (
              <button
                type="button"
                disabled
                className="w-full rounded-2xl bg-warm-200 py-3.5 text-sm font-bold text-warm-500 cursor-not-allowed mb-6"
              >
                Produto Esgotado
              </button>
            )}

            {/* Trust badges */}
            <div className="border-t border-warm-200 pt-6 grid grid-cols-3 gap-3 text-center">
              <div className="flex flex-col items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5 text-kintsugi-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={1.5}
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span className="text-xs text-warm-500">Peca Autentica</span>
              </div>
              <div className="flex flex-col items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5 text-kintsugi-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={1.5}
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18C2.504 7.5 2 8.004 2 8.625v1.5c0 .621.504 1.125 1.125 1.125z"
                  />
                </svg>
                <span className="text-xs text-warm-500">Embalagem Premium</span>
              </div>
              <div className="flex flex-col items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-5 w-5 text-kintsugi-500"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={1.5}
                  aria-hidden="true"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                  />
                </svg>
                <span className="text-xs text-warm-500">30 dias para trocar</span>
              </div>
            </div>
          </div>
        </div>

        {/* Related products */}
        {related_products && related_products.length > 0 && (
          <section className="mt-16" aria-labelledby="related-heading">
            <h2
              id="related-heading"
              className="font-display text-xl font-extrabold text-warm-700 mb-6"
            >
              Você também pode gostar
            </h2>
            <ProductGrid products={related_products} />
          </section>
        )}
      </div>
    </PublicLayout>
  );
}
