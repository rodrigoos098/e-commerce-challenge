import { router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { Product } from '@/types/admin';

interface LowStockProps {
    products: Product[];
}

function getDeficit(product: Product): number {
    return product.min_quantity - product.quantity;
}

function fillPercent(product: Product): number {
    return product.min_quantity > 0 ? Math.min((product.quantity / product.min_quantity) * 100, 100) : 0;
}

function getSeverityBadge(product: Product) {
    if (product.quantity === 0) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                <span className="inline-block h-1.5 w-1.5 rounded-full bg-red-500" />
                Esgotado
            </span>
        );
    }

    const percentage = product.quantity / product.min_quantity;

    if (percentage <= 0.25) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700">
                <span className="inline-block h-1.5 w-1.5 rounded-full bg-orange-500" />
                Critico
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
            <span className="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" />
            Baixo
        </span>
    );
}

function rowBorderClass(product: Product): string {
    if (product.quantity === 0) {
        return 'border-l-red-400 bg-red-50/40';
    }

    const percentage = product.quantity / product.min_quantity;

    if (percentage <= 0.25) {
        return 'border-l-orange-400 bg-orange-50/40';
    }

    return 'border-l-amber-400 bg-amber-50/40';
}

function barClass(product: Product): string {
    if (product.quantity === 0) {
        return 'bg-red-500';
    }

    const percentage = product.quantity / product.min_quantity;

    if (percentage <= 0.25) {
        return 'bg-orange-500';
    }

    return 'bg-amber-400';
}

export default function LowStock({ products }: LowStockProps) {
    const sortedProducts = [...products].sort((left, right) => {
        if (left.quantity === 0 && right.quantity !== 0) {
            return -1;
        }

        if (right.quantity === 0 && left.quantity !== 0) {
            return 1;
        }

        return getDeficit(right) - getDeficit(left);
    });

    const outOfStockCount = products.filter((product) => product.quantity === 0).length;
    const criticalCount = products.filter(
        (product) => product.quantity > 0 && product.quantity / product.min_quantity <= 0.25,
    ).length;

    return (
        <AdminLayout title="Estoque Baixo">
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-warm-700">Relatorio de Estoque Baixo</h1>
                    <p className="mt-0.5 text-sm text-warm-500">
                        Produtos com quantidade abaixo do minimo configurado
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border border-warm-200 bg-white p-5 shadow-xs">
                        <p className="text-xs font-semibold uppercase tracking-wider text-warm-500">Total de Alertas</p>
                        <p className="mt-1 text-3xl font-bold text-warm-700">{products.length}</p>
                        <p className="mt-1 text-xs text-warm-500">produtos abaixo do minimo</p>
                    </div>
                    <div className="rounded-xl border border-red-200 bg-red-50 p-5">
                        <p className="text-xs font-semibold uppercase tracking-wider text-red-600">Esgotados</p>
                        <p className="mt-1 text-3xl font-bold text-red-700">{outOfStockCount}</p>
                        <p className="mt-1 text-xs text-red-500">quantidade = 0</p>
                    </div>
                    <div className="rounded-xl border border-orange-200 bg-orange-50 p-5">
                        <p className="text-xs font-semibold uppercase tracking-wider text-orange-600">Criticos</p>
                        <p className="mt-1 text-3xl font-bold text-orange-700">{criticalCount}</p>
                        <p className="mt-1 text-xs text-orange-500">ate 25% do minimo</p>
                    </div>
                </div>

                {sortedProducts.length === 0 ? (
                    <div className="rounded-xl border border-warm-200 bg-white p-12 text-center shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" className="mx-auto mb-4 h-12 w-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-base font-semibold text-warm-700">Tudo em dia!</p>
                        <p className="mt-1 text-sm text-warm-500">Nenhum produto com estoque abaixo do minimo.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-warm-200 bg-white shadow-xs">
                        <div className="flex items-center justify-between border-b border-warm-200 px-6 py-4">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-warm-600">
                                Produtos em Alerta
                            </h2>
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/products')}
                                className="text-xs font-medium text-kintsugi-600 hover:text-kintsugi-700"
                            >
                                Ver todos os produtos
                            </button>
                        </div>

                        <div className="divide-y divide-gray-100">
                            {sortedProducts.map((product) => {
                                const percentage = fillPercent(product);

                                return (
                                    <div key={product.id} className={`border-l-4 px-6 py-4 ${rowBorderClass(product)}`}>
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm font-semibold text-warm-700">{product.name}</p>
                                                    {getSeverityBadge(product)}
                                                </div>
                                                <p className="mt-0.5 text-xs text-warm-500">
                                                    {product.category?.name ?? 'Sem categoria'} · <code className="font-mono">{product.slug}</code>
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 items-center gap-5">
                                                <div className="text-center">
                                                    <p className="text-xs text-warm-500">Atual</p>
                                                    <p className={`text-lg font-bold ${product.quantity === 0 ? 'text-red-600' : 'text-warm-700'}`}>
                                                        {product.quantity}
                                                    </p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xs text-warm-500">Minimo</p>
                                                    <p className="text-lg font-bold text-warm-400">{product.min_quantity}</p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xs text-warm-500">Deficit</p>
                                                    <p className="text-lg font-bold text-red-600">-{getDeficit(product)}</p>
                                                </div>

                                                <div className="hidden w-20 sm:block">
                                                    <p className="mb-1 text-right text-xs text-warm-500">{Math.round(percentage)}%</p>
                                                    <div className="h-2 overflow-hidden rounded-full bg-gray-200">
                                                        <div
                                                            className={`h-2 rounded-full transition-all ${barClass(product)}`}
                                                            style={{ width: `${percentage}%` }}
                                                        />
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() => router.visit(`/admin/products/${product.id}/edit`)}
                                                    className="whitespace-nowrap rounded-lg border border-kintsugi-200 bg-white px-3 py-1.5 text-xs font-medium text-kintsugi-700 transition-colors hover:bg-kintsugi-50"
                                                >
                                                    Restock
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
