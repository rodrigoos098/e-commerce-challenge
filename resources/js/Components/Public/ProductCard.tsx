import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
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
    const { playCartSound } = useCartSound();

    const { auth } = usePage<any>().props;
    const user = auth?.user;

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (product.quantity === 0 || adding) { return; }

        if (!user) {
            toast.error('Você precisa de uma conta para adicionar itens ao carrinho.');
            router.get('/login');
            return;
        }

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
        <article className="group relative flex flex-col rounded-2xl bg-white border border-warm-100 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:-translate-y-1 hover:border-warm-200 transition duration-300 overflow-hidden">
            {/* Image */}
            <Link href={`/products/${product.slug}`} className="block overflow-hidden aspect-square bg-warm-50 relative">
                {product.category && (
                    <span className="absolute top-4 left-4 z-10 max-w-[60%] truncate rounded-full bg-white/90 backdrop-blur-md px-3 py-1 text-xs font-semibold text-warm-700 shadow-sm transition-transform group-hover:scale-105">
                        {product.category.name}
                    </span>
                )}
                {isOutOfStock && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-black/20 backdrop-blur-[2px]">
                        <span className="rounded-full bg-white px-4 py-1.5 text-xs font-bold tracking-wider text-warm-800 shadow-lg">ESGOTADO</span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-warm-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zM8.25 8.25h.008v.008H8.25V8.25z" />
                    </svg>
                </div>
            </Link>

            {/* Content */}
            <div className="flex flex-1 flex-col p-6">
                {/* Name */}
                <Link href={`/products/${product.slug}`} className="block mb-1">
                    <h3 className="text-[1.05rem] font-medium text-warm-800 line-clamp-2 leading-relaxed group-hover:text-kintsugi-600 transition-colors">
                        {product.name}
                    </h3>
                </Link>

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
                <div className="mb-4">
                    <StockBadge quantity={product.quantity} minQuantity={product.min_quantity} />
                </div>

                {/* Price + CTA */}
                <div className="mt-auto flex items-end justify-between gap-4 pt-2">
                    <div>
                        <p className="font-display text-xl font-bold text-warm-800">{formatPrice(product.price)}</p>
                    </div>
                    <button
                        onClick={handleAddToCart}
                        disabled={isOutOfStock || adding}
                        aria-label={adding ? 'Adicionando...' : `Adicionar ${product.name}`}
                        className="flex h-11 w-11 items-center justify-center rounded-full bg-warm-50 text-warm-600 hover:bg-kintsugi-50 hover:text-kintsugi-600 active:scale-90 transition duration-300 disabled:opacity-40 disabled:cursor-not-allowed border border-warm-100 hover:border-kintsugi-200"
                        title="Adicionar ao carrinho"
                    >
                        {adding ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        )}
                    </button>
                </div>
            </div>
        </article>
    );
}
