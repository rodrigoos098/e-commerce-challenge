import React from 'react';
import ProductCard from '@/Components/Public/ProductCard';
import SkeletonLoader from '@/Components/Shared/SkeletonLoader';
import type { Product } from '@/types/public';

interface ProductGridProps {
    products: Product[];
    loading?: boolean;
    emptyMessage?: string;
    onClearFilters?: () => void;
}

export default function ProductGrid({ products, loading = false, emptyMessage = 'Nenhum produto encontrado.', onClearFilters }: ProductGridProps) {
    if (loading) {
        return (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                {Array.from({ length: 8 }).map((_, i) => (
                    <SkeletonLoader key={i} type="card" />
                ))}
            </div>
        );
    }

    if (products.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-24 px-4 text-center">
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-kintsugi-50 mb-6 animate-float">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="h-9 w-9 text-kintsugi-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={1.2}
                        aria-hidden="true"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <p className="font-display text-xl font-extrabold text-warm-700">{emptyMessage}</p>
                <p className="text-sm text-warm-500 mt-2">
                    {onClearFilters
                        ? 'Nenhuma peça corresponde a esses filtros. Tente ajustá-los.'
                        : 'Em breve novas peças chegam à coleção.'}
                </p>
                {onClearFilters && (
                    <button
                        type="button"
                        onClick={onClearFilters}
                        className="mt-5 inline-flex items-center gap-2 rounded-full border border-warm-200 bg-white px-5 py-2 text-sm font-semibold text-warm-600 hover:border-kintsugi-300 hover:text-kintsugi-600 transition-colors shadow-sm"
                    >
                        Limpar filtros
                    </button>
                )}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            {products.map((product, idx) => (
                <ProductCard key={product.id} product={product} priority={idx < 4} />
            ))}
        </div>
    );
}
