import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Modal from '@/Components/Admin/Modal';
import type { Product, StockMovement } from '@/types/admin';

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

const movementTypeConfig = {
    entrada: { label: 'Entrada', classes: 'bg-emerald-100 text-emerald-700', sign: '+' },
    saida: { label: 'Saida', classes: 'bg-red-100 text-red-700', sign: '-' },
    ajuste: { label: 'Ajuste', classes: 'bg-amber-100 text-amber-700', sign: '+/-' },
    venda: { label: 'Venda', classes: 'bg-red-100 text-red-700', sign: '-' },
    devolucao: { label: 'Devolucao', classes: 'bg-blue-100 text-blue-700', sign: '+' },
} as const;

interface ProductsShowProps {
    product: Product;
    movements: StockMovement[];
}

export default function ProductsShow({
    product,
    movements,
}: ProductsShowProps) {
    const [deleteModal, setDeleteModal] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const margin = product.cost_price
        ? ((product.price - product.cost_price) / product.price) * 100
        : null;

    const isLow = product.quantity <= product.min_quantity;
    const isOut = product.quantity === 0;

    function handleDelete() {
        setDeleting(true);
        router.delete(`/admin/products/${product.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteModal(false);
            },
        });
    }

    return (
        <AdminLayout title={product.name}>
            <div className="p-6">
                <div className="max-w-4xl mx-auto space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/products')}
                                className="mb-2 flex items-center gap-1.5 text-sm text-warm-500 transition-colors hover:text-kintsugi-600"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Voltar para Produtos
                            </button>
                            <h1 className="text-2xl font-bold text-warm-700">{product.name}</h1>
                            <div className="mt-1.5 flex items-center gap-2">
                                <span className="text-sm text-warm-400">{product.category?.name ?? 'Sem categoria'}</span>
                                <span className="text-warm-300">·</span>
                                <span
                                    className={[
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                        product.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-warm-500',
                                    ].join(' ')}
                                >
                                    <span className={['h-1.5 w-1.5 rounded-full', product.active ? 'bg-emerald-500' : 'bg-gray-400'].join(' ')} />
                                    {product.active ? 'Ativo' : 'Inativo'}
                                </span>
                            </div>
                        </div>
                        <div className="flex flex-shrink-0 items-center gap-2">
                            <Link
                                href={`/admin/products/${product.id}/edit`}
                                className="flex items-center gap-2 rounded-lg bg-kintsugi-50 px-4 py-2 text-sm font-medium text-kintsugi-600 transition-colors hover:bg-kintsugi-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Editar
                            </Link>
                            <button
                                type="button"
                                onClick={() => setDeleteModal(true)}
                                className="flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Excluir
                            </button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <div className="space-y-4 rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
                                <h2 className="text-sm font-semibold uppercase tracking-wider text-warm-600">Descricao</h2>
                                <p className="text-sm leading-relaxed text-warm-600">{product.description}</p>

                                {product.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 pt-1">
                                        {product.tags.map((tag) => (
                                            <span key={tag.id} className="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-warm-600">
                                                {tag.name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="rounded-xl border border-warm-200 bg-white p-6 shadow-xs">
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-warm-600">Precos</h2>
                                <div className="grid grid-cols-3 gap-4">
                                    <div>
                                        <p className="text-xs uppercase tracking-wide text-warm-400">Venda</p>
                                        <p className="mt-0.5 text-xl font-bold text-warm-700">{formatCurrency(product.price)}</p>
                                    </div>
                                    {product.cost_price !== undefined && (
                                        <div>
                                            <p className="text-xs uppercase tracking-wide text-warm-400">Custo</p>
                                            <p className="mt-0.5 text-xl font-bold text-warm-600">{formatCurrency(product.cost_price)}</p>
                                        </div>
                                    )}
                                    {margin !== null && (
                                        <div>
                                            <p className="text-xs uppercase tracking-wide text-warm-400">Margem</p>
                                            <p className={['mt-0.5 text-xl font-bold', margin > 20 ? 'text-emerald-600' : 'text-amber-600'].join(' ')}>
                                                {margin.toFixed(1)}%
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="overflow-hidden rounded-xl border border-warm-200 bg-white shadow-xs">
                                <div className="border-b border-warm-200 px-5 py-4">
                                    <h2 className="text-sm font-semibold uppercase tracking-wider text-warm-600">Movimentacoes de Estoque</h2>
                                </div>
                                {movements.length === 0 ? (
                                    <p className="px-5 py-8 text-center text-sm text-warm-400">Nenhuma movimentacao registrada.</p>
                                ) : (
                                    <div className="divide-y divide-gray-100">
                                        {movements.map((movement) => {
                                            const config = movementTypeConfig[movement.type] ?? movementTypeConfig.ajuste;
                                            const isDecrease = movement.type === 'saida' || movement.type === 'venda';

                                            return (
                                                <div key={movement.id} className="flex items-center justify-between px-5 py-3 transition-colors hover:bg-warm-50">
                                                    <div className="flex items-center gap-3">
                                                        <span className={['rounded-full px-2 py-0.5 text-xs font-semibold', config.classes].join(' ')}>
                                                            {config.label}
                                                        </span>
                                                        <span className="text-sm text-warm-500">{movement.notes ?? '-'}</span>
                                                    </div>
                                                    <div className="ml-4 flex-shrink-0 text-right">
                                                        <p className={['text-sm font-bold', isDecrease ? 'text-red-600' : 'text-emerald-600'].join(' ')}>
                                                            {config.sign}
                                                            {movement.quantity}
                                                        </p>
                                                        <p className="text-xs text-warm-400">{formatDate(movement.created_at)}</p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div
                                className={[
                                    'rounded-xl border p-5 shadow-xs',
                                    isOut ? 'border-red-200 bg-red-50' : isLow ? 'border-amber-200 bg-amber-50' : 'border-warm-200 bg-white',
                                ].join(' ')}
                            >
                                <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-warm-500">Estoque Atual</p>
                                <p className={['text-5xl font-black', isOut ? 'text-red-600' : isLow ? 'text-amber-500' : 'text-warm-700'].join(' ')}>
                                    {product.quantity}
                                </p>
                                <p className="mt-1 text-sm text-warm-400">unidades</p>
                                <div className="mt-3 border-t border-warm-200/60 pt-3">
                                    <p className="text-xs text-warm-500">
                                        Minimo: <span className="font-semibold text-warm-600">{product.min_quantity} un.</span>
                                    </p>
                                </div>
                                {isOut && (
                                    <div className="mt-3 rounded-lg bg-red-100 p-2.5">
                                        <p className="text-xs font-semibold text-red-700">Produto esgotado.</p>
                                    </div>
                                )}
                                {!isOut && isLow && (
                                    <div className="mt-3 rounded-lg bg-amber-100 p-2.5">
                                        <p className="text-xs font-semibold text-amber-700">Estoque abaixo do minimo.</p>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-3 rounded-xl border border-warm-200 bg-white p-5 shadow-xs">
                                <p className="text-xs font-semibold uppercase tracking-wider text-warm-500">Informacoes</p>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-warm-500">ID</span>
                                        <span className="font-medium text-warm-600">#{product.id}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-warm-500">Slug</span>
                                        <span className="ml-4 truncate font-medium text-warm-600">{product.slug}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-warm-500">Criado em</span>
                                        <span className="font-medium text-warm-600">{formatDate(product.created_at)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-warm-500">Atualizado</span>
                                        <span className="font-medium text-warm-600">{formatDate(product.updated_at)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal
                isOpen={deleteModal}
                onClose={() => setDeleteModal(false)}
                title="Excluir Produto"
                onConfirm={handleDelete}
                confirmLabel="Excluir"
                cancelLabel="Cancelar"
                confirmDestructive
                loading={deleting}
            >
                <p className="text-sm text-warm-600">
                    Tem certeza que deseja excluir <strong className="font-semibold">{product.name}</strong>?
                    Esta acao nao pode ser desfeita.
                </p>
            </Modal>
        </AdminLayout>
    );
}
