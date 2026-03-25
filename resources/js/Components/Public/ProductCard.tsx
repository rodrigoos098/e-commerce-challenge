import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { formatPrice } from '@/utils/format';
import type { Product } from '@/types/public';

interface ProductCardProps {
    product: Product;
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

export default function ProductCard({ product }: ProductCardProps) {
    const [adding, setAdding] = useState(false);

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

    const isOutOfStock = product.quantity === 0;

    return (
        <article className="group relative flex flex-col rounded-2xl bg-white border border-warm-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-kintsugi-200 transition-all duration-300 overflow-hidden">
            {/* Image */}
            <Link href={`/products/${product.slug}`} className="block overflow-hidden aspect-square bg-warm-100">
                {product.category && (
                    <span className="absolute top-3 left-3 z-10 max-w-[60%] truncate rounded-full bg-white/80 backdrop-blur-sm px-2.5 py-0.5 text-xs font-medium text-warm-600 border border-warm-200">
                        {product.category.name}
                    </span>
                )}
                {isOutOfStock && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-black/30 backdrop-blur-sm rounded-none">
                        <span className="rounded-full bg-white/90 px-4 py-1.5 text-xs font-bold text-warm-700">Esgotado</span>
                    </div>
                )}
                <img
                    src={product.image_url ?? `/storage/products/${product.id}.webp`}
                    alt={product.name}
                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                    onError={(e) => {
                        const target = e.currentTarget;
                        target.style.display = 'none';
                        const fallback = target.parentElement?.querySelector('.product-placeholder');
                        if (fallback) { (fallback as HTMLElement).style.display = 'flex'; }
                    }}
                />
                <div className="product-placeholder hidden h-full w-full items-center justify-center bg-warm-100" style={{ display: 'none' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-warm-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zM8.25 8.25h.008v.008H8.25V8.25z" />
                    </svg>
                </div>
            </Link>

            {/* Content */}
            <div className="flex flex-1 flex-col p-5">
                {/* Tags */}
                {product.tags && product.tags.length > 0 && (
                    <div className="mb-2 flex flex-wrap gap-1">
                        {product.tags.slice(0, 2).map((tag) => (
                            <span key={tag.id} className="rounded-full bg-kintsugi-50 px-2 py-0.5 text-xs text-kintsugi-500 font-medium">
                                {tag.name}
                            </span>
                        ))}
                    </div>
                )}

                {/* Name */}
                <Link href={`/products/${product.slug}`} className="block">
                    <h3 className="text-base font-semibold text-warm-700 line-clamp-2 leading-snug hover:text-kintsugi-500 transition-colors">
                        {product.name}
                    </h3>
                </Link>

                {/* Stock */}
                <div className="mt-1.5">
                    <StockBadge quantity={product.quantity} minQuantity={product.min_quantity} />
                </div>

                {/* Price + CTA */}
                <div className="mt-auto pt-4 flex items-center justify-between gap-2">
                    <div>
                        <p className="font-display text-lg font-extrabold text-warm-700">{formatPrice(product.price)}</p>
                    </div>
                    <button
                        onClick={handleAddToCart}
                        disabled={isOutOfStock || adding}
                        aria-label={adding ? 'Adicionando ao carrinho…' : `Adicionar ${product.name} ao carrinho`}
                        aria-busy={adding}
                        className="flex items-center gap-1.5 rounded-xl bg-kintsugi-500 px-3 py-2 text-xs font-semibold text-white hover:bg-kintsugi-600 active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Adicionar
                    </button>
                </div>
            </div>
        </article>
    );
}
