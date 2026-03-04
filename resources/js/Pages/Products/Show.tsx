import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import PublicLayout from '@/Layouts/PublicLayout';
import QuantitySelector from '@/Components/Public/QuantitySelector';
import ProductGrid from '@/Components/Public/ProductGrid';
import type { ProductShowPageProps } from '@/types/public';

// ——— Helpers ————————————————————————————————————————————————

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

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

    const isOutOfStock = product.quantity === 0;

    const handleAddToCart = () => {
        if (isOutOfStock) { return; }
        setAdding(true);
        router.post(
            '/cart/items',
            { product_id: product.id, quantity },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`"${product.name}" adicionado ao carrinho!`);
                    setAdding(false);
                },
                onError: () => {
                    toast.error('Erro ao adicionar ao carrinho.');
                    setAdding(false);
                },
            },
        );
    };

    return (
        <PublicLayout title={product.name}>
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
                {/* Breadcrumb */}
                <nav aria-label="Navegação" className="mb-8 flex items-center gap-2 text-sm text-gray-400">
                    <Link href="/" className="hover:text-violet-600 transition-colors">Início</Link>
                    <span aria-hidden="true">/</span>
                    <Link href="/products" className="hover:text-violet-600 transition-colors">Produtos</Link>
                    {product.category && (
                        <>
                            <span aria-hidden="true">/</span>
                            <Link href={`/products?category_id=${product.category.id}`} className="hover:text-violet-600 transition-colors">
                                {product.category.name}
                            </Link>
                        </>
                    )}
                    <span aria-hidden="true">/</span>
                    <span className="text-gray-600 font-medium line-clamp-1">{product.name}</span>
                </nav>

                {/* Product detail */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
                    {/* Images */}
                    <div className="space-y-4">
                        <div className="overflow-hidden rounded-2xl bg-gray-100 border border-gray-200 aspect-square">
                            <img
                                src={`https://picsum.photos/seed/${product.id}/800/800`}
                                alt={product.name}
                                className="h-full w-full object-cover transition-transform duration-700 hover:scale-105"
                            />
                        </div>
                        {/* Thumbnail row */}
                        <div className="flex gap-3">
                            {[product.id, product.id + 100, product.id + 200].map((seed) => (
                                <button
                                    key={seed}
                                    className="h-20 w-20 overflow-hidden rounded-xl border-2 border-violet-200 bg-gray-100 hover:border-violet-500 transition-colors"
                                    aria-label="Ver imagem"
                                >
                                    <img
                                        src={`https://picsum.photos/seed/${seed}/160/160`}
                                        alt=""
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                        aria-hidden="true"
                                    />
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Info */}
                    <div className="flex flex-col">
                        {/* Category & Tags */}
                        <div className="flex flex-wrap items-center gap-2 mb-4">
                            {product.category && (
                                <Link
                                    href={`/products?category_id=${product.category.id}`}
                                    className="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 hover:bg-violet-200 transition-colors"
                                >
                                    {product.category.name}
                                </Link>
                            )}
                            {product.tags.map((tag) => (
                                <span key={tag.id} className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                    #{tag.name}
                                </span>
                            ))}
                        </div>

                        {/* Name */}
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight mb-4">
                            {product.name}
                        </h1>

                        {/* Price */}
                        <div className="mb-6">
                            <p className="text-4xl font-extrabold text-gray-900">{formatPrice(product.price)}</p>
                            <p className="mt-1 text-sm text-gray-400">
                                Em até 12× de {formatPrice(product.price / 12)} sem juros
                            </p>
                        </div>

                        {/* Stock indicator */}
                        <div className="mb-6">
                            <StockIndicator quantity={product.quantity} minQuantity={product.min_quantity} />
                        </div>

                        {/* Description */}
                        <p className="text-sm text-gray-600 leading-relaxed mb-8">{product.description}</p>

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
                                    className="flex-1 sm:flex-none flex items-center justify-center gap-2 rounded-2xl bg-violet-600 px-8 py-3.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-lg shadow-violet-200 disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {adding ? (
                                        <>
                                            <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Adicionando...
                                        </>
                                    ) : (
                                        <>
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5} aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
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
                                className="w-full rounded-2xl bg-gray-200 py-3.5 text-sm font-bold text-gray-500 cursor-not-allowed mb-6"
                            >
                                Produto Esgotado
                            </button>
                        )}

                        {/* Trust badges */}
                        <div className="border-t border-gray-100 pt-6 grid grid-cols-3 gap-3 text-center">
                            {[
                                { icon: '🔒', label: 'Compra Segura' },
                                { icon: '🚚', label: 'Frete Rápido' },
                                { icon: '↩️', label: '30 dias para trocar' },
                            ].map((badge) => (
                                <div key={badge.label} className="flex flex-col items-center gap-1">
                                    <span className="text-2xl" aria-hidden="true">{badge.icon}</span>
                                    <span className="text-xs text-gray-500">{badge.label}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Related products */}
                {related_products && related_products.length > 0 && (
                    <section className="mt-16" aria-labelledby="related-heading">
                        <h2 id="related-heading" className="text-xl font-bold text-gray-900 mb-6">Você também pode gostar</h2>
                        <ProductGrid products={related_products} />
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
