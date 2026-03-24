import React from 'react';
import ProductCard from '@/Components/Public/ProductCard';
import SkeletonLoader from '@/Components/Shared/SkeletonLoader';
import type { Product } from '@/types/public';

interface ProductGridProps {
    products: Product[];
    loading?: boolean;
    emptyMessage?: string;
}

export default function ProductGrid({ products, loading = false, emptyMessage = 'Nenhum produto encontrado.' }: ProductGridProps) {
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
            <div className="flex flex-col items-center justify-center py-20 px-4 text-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-16 w-16 text-gray-200 mb-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={1}
                    aria-hidden="true"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p className="text-lg font-semibold text-warm-500">{emptyMessage}</p>
                <p className="text-sm text-warm-400 mt-1">Tente ajustar os filtros ou busca.</p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            {products.map((product) => (
                <ProductCard key={product.id} product={product} />
            ))}
        </div>
    );
}
