import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { formatPrice } from '@/utils/format';
import { useCartSound } from '@/hooks/useCartSound';
import type { Product } from '@/types/public';

interface ProductCardProps {
    product: Product;
    priority?: boolean;
}

function StockBadge({ quantity, minQuantity }: { quantity: number; minQuantity: number }) {
    if (quantity === 0) {
        return <span className="text-xs font-medium text-red-500">Esgotado</span>;
    }
    if (quantity <= minQuantity) {
        return <span className="text-xs font-medium text-amber-500">Últimas unidades</span>;
    }
    return <span className="text-xs font-medium text-green-600">Em estoque</span>;
}

export default function ProductCard({ product, priority = false }: ProductCardProps) {
    const [adding, setAdding] = useState(false);
    const [confirmAdd, setConfirmAdd] = useState(false);
    const { playCartSound } = useCartSound();

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (product.quantity === 0 || adding) { return; }

        setAdding(true);
        router.post(
            '/cart/items',
            { product_id: product.id, quantity: 1 },
            {
                preserveScroll: true,
                onSuccess: () => {
                    playCartSound();
                    toast.success(`"${product.name}" adicionado ao carrinho!`);
                    setAdding(false);
                    setConfirmAdd(false);
                },
                onError: () => {
                    toast.error('Erro ao adicionar ao carrinho.');
                    setAdding(false);
                },
            },
        );
    };

    const isOutOfStock = product.quantity === 0;

    return (
        <article 
            className="group relative flex flex-col rounded-2xl border border-warm-200/70 hover:border-warm-200 bg-white/40 p-3 transition duration-300"
            onMouseLeave={() => setConfirmAdd(false)}
        >
            {/* Image */}
            <div className="block overflow-hidden rounded-xl aspect-square bg-warm-50 relative mb-4">
                {product.category && (
                    <span className="absolute top-4 left-4 z-10 max-w-[60%] truncate rounded-full bg-parchment border border-warm-200 px-3 py-1 text-xs font-semibold text-warm-800 shadow-sm transition-transform group-hover:scale-105">
                        {product.category.name}
                    </span>
                )}
                {isOutOfStock && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-warm-100/40">
                        <span className="rounded-full bg-warm-800 border border-warm-900 px-4 py-1.5 text-xs font-bold tracking-wider text-warm-50 shadow-sm">ESGOTADO</span>
                    </div>
                )}
                <img
                    src={product.image_url ?? `/storage/products/${product.id}.webp`}
                    alt={product.name}
                    className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                    loading={priority ? 'eager' : 'lazy'}
                    fetchPriority={priority ? 'high' : 'auto'}
                    onError={(e) => {
                        const target = e.currentTarget;
                        target.style.display = 'none';
                        const fallback = target.parentElement?.querySelector('.product-placeholder');
                        if (fallback) { (fallback as HTMLElement).style.display = 'flex'; }
                    }}
                />
                <div className="product-placeholder hidden h-full w-full items-center justify-center bg-warm-50" style={{ display: 'none' }}>
                    <span className="text-xs font-medium text-warm-400 uppercase tracking-widest border border-warm-200 px-4 py-2 rounded-full">Imagem indisponível</span>
                </div>
            </div>

            {/* Content */}
            <div className="flex flex-1 flex-col px-1">
                {/* Name */}
                <h3 className="text-base font-medium text-warm-800 line-clamp-2 leading-snug group-hover:text-kintsugi-600 transition-colors mb-1">
                    <Link href={`/products/${product.slug}`} className="before:absolute before:inset-0 before:z-0 outline-none">
                        {product.name}
                    </Link>
                </h3>

                {/* Tags */}
                {product.tags && product.tags.length > 0 && (
                    <div className="mb-3 flex flex-wrap gap-1.5 opacity-80">
                        {product.tags.slice(0, 2).map((tag) => (
                            <span key={tag.id} className="text-[0.65rem] uppercase tracking-wider text-warm-500 font-medium">
                                #{tag.name}
                            </span>
                        ))}
                    </div>
                )}

                {/* Stock */}
                <div className="mb-3">
                    <StockBadge quantity={product.quantity} minQuantity={product.min_quantity} />
                </div>

                {/* Price + CTA */}
                <div className="mt-auto flex items-end justify-between gap-4 pt-1">
                    <div>
                        <p className="font-display text-lg font-semibold text-warm-700">{formatPrice(product.price)}</p>
                    </div>
                    <button
                        onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            if (!confirmAdd) {
                                setConfirmAdd(true);
                            } else {
                                handleAddToCart(e);
                            }
                        }}
                        disabled={isOutOfStock || adding}
                        aria-label={adding ? 'Adicionando...' : `Adicionar ${product.name}`}
                        className={`relative z-10 flex items-center justify-center rounded-full transition-all duration-300 ${
                            confirmAdd
                                ? 'h-9 px-4 bg-kintsugi-50 border border-kintsugi-200 text-kintsugi-600 shadow-sm'
                                : 'h-10 w-10 bg-transparent text-warm-400 hover:bg-warm-50 hover:text-kintsugi-600 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed'
                        }`}
                        title="Adicionar ao carrinho"
                    >
                        {confirmAdd ? (
                            <span className="text-xs font-bold whitespace-nowrap">
                                {adding ? 'Adicionando...' : 'Adicionar ao carrinho'}
                            </span>
                        ) : adding ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 opacity-80 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        )}
                    </button>
                </div>
            </div>
        </article>
    );
}
