import React from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import type { Product } from '@/types/public';

interface ProductCardProps {
    product: Product;
}

function formatPrice(value: number): string {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
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
    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (product.quantity === 0) { return; }

        router.post(
            '/cart/items',
            { product_id: product.id, quantity: 1 },
            {
                preserveScroll: true,
                onSuccess: () => toast.success(`"${product.name}" adicionado ao carrinho!`),
                onError: () => toast.error('Erro ao adicionar ao carrinho.'),
            },
        );
    };

    const isOutOfStock = product.quantity === 0;

    return (
        <article className="group relative flex flex-col rounded-2xl bg-white border border-warm-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            {/* Image */}
            <Link href={`/products/${product.slug}`} className="block overflow-hidden aspect-square bg-gray-100">
                {product.category && (
                    <span className="absolute top-3 left-3 z-10 rounded-full bg-white/80 backdrop-blur-sm px-2.5 py-0.5 text-xs font-medium text-warm-600 border border-warm-200">
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
                />
            </Link>

            {/* Content */}
            <div className="flex flex-1 flex-col p-4">
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
                    <h3 className="text-sm font-semibold text-warm-700 line-clamp-2 leading-snug hover:text-kintsugi-500 transition-colors">
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
                        <p className="text-lg font-extrabold text-warm-700">{formatPrice(product.price)}</p>
                        {product.cost_price && (
                            <p className="text-xs text-warm-400">
                                Custo: {formatPrice(product.cost_price)}
                            </p>
                        )}
                    </div>
                    <button
                        onClick={handleAddToCart}
                        disabled={isOutOfStock}
                        aria-label={`Adicionar ${product.name} ao carrinho`}
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
