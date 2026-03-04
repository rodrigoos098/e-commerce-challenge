import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Modal from '@/Components/Admin/Modal';
import type { Product, StockMovement } from '@/types/admin';

// — Helpers ——————————————————————————
function formatCurrency(v: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
}

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso));
}

const movementTypeConfig = {
    in: { label: 'Entrada', classes: 'bg-emerald-100 text-emerald-700', sign: '+' },
    out: { label: 'Saída', classes: 'bg-red-100 text-red-700', sign: '-' },
    adjustment: { label: 'Ajuste', classes: 'bg-amber-100 text-amber-700', sign: '±' },
    return: { label: 'Devolução', classes: 'bg-blue-100 text-blue-700', sign: '+' },
};

// — Props ——————————————————————
interface ProductsShowProps {
    product: Product;
    movements: StockMovement[];
}

// — Component ——————————————————————
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
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <button
                                type="button"
                                onClick={() => router.visit('/admin/products')}
                                className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 mb-2 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Voltar para Produtos
                            </button>
                            <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
                            <div className="flex items-center gap-2 mt-1.5">
                                <span className="text-sm text-gray-400">{product.category.name}</span>
                                <span className="text-gray-300">·</span>
                                <span className={[
                                    'inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full',
                                    product.active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500',
                                ].join(' ')}>
                                    <span className={['h-1.5 w-1.5 rounded-full', product.active ? 'bg-emerald-500' : 'bg-gray-400'].join(' ')} />
                                    {product.active ? 'Ativo' : 'Inativo'}
                                </span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 flex-shrink-0">
                            <Link
                                href={`/admin/products/${product.id}/edit`}
                                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Editar
                            </Link>
                            <button
                                onClick={() => setDeleteModal(true)}
                                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Excluir
                            </button>
                        </div>
                    </div>

                    {/* Main grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Left: product details */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Info card */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6 space-y-4">
                                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">Descrição</h2>
                                <p className="text-sm text-gray-700 leading-relaxed">{product.description}</p>

                                {product.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-2 pt-1">
                                        {product.tags.map((tag) => (
                                            <span key={tag.id} className="px-2.5 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                                                {tag.name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Pricing */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
                                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Preços</h2>
                                <div className="grid grid-cols-3 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-400 uppercase tracking-wide">Venda</p>
                                        <p className="text-xl font-bold text-gray-900 mt-0.5">{formatCurrency(product.price)}</p>
                                    </div>
                                    {product.cost_price !== undefined && (
                                        <div>
                                            <p className="text-xs text-gray-400 uppercase tracking-wide">Custo</p>
                                            <p className="text-xl font-bold text-gray-700 mt-0.5">{formatCurrency(product.cost_price)}</p>
                                        </div>
                                    )}
                                    {margin !== null && (
                                        <div>
                                            <p className="text-xs text-gray-400 uppercase tracking-wide">Margem</p>
                                            <p className={['text-xl font-bold mt-0.5', margin > 20 ? 'text-emerald-600' : 'text-amber-600'].join(' ')}>
                                                {margin.toFixed(1)}%
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Stock movements */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                                <div className="px-5 py-4 border-b border-gray-200">
                                    <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">Movimentações de Estoque</h2>
                                </div>
                                {movements.length === 0 ? (
                                    <p className="px-5 py-8 text-sm text-center text-gray-400">Nenhuma movimentação registrada.</p>
                                ) : (
                                    <div className="divide-y divide-gray-100">
                                        {movements.map((mv) => {
                                            const conf = movementTypeConfig[mv.type];
                                            return (
                                                <div key={mv.id} className="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                                                    <div className="flex items-center gap-3">
                                                        <span className={['text-xs font-semibold px-2 py-0.5 rounded-full', conf.classes].join(' ')}>
                                                            {conf.label}
                                                        </span>
                                                        <span className="text-sm text-gray-500">{mv.notes ?? '—'}</span>
                                                    </div>
                                                    <div className="text-right flex-shrink-0 ml-4">
                                                        <p className={[
                                                            'text-sm font-bold',
                                                            mv.type === 'out' ? 'text-red-600' : 'text-emerald-600',
                                                        ].join(' ')}>
                                                            {conf.sign}{mv.quantity}
                                                        </p>
                                                        <p className="text-xs text-gray-400">{formatDate(mv.created_at)}</p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Right: stock widget */}
                        <div className="space-y-4">
                            <div className={[
                                'rounded-xl border shadow-xs p-5',
                                isOut ? 'bg-red-50 border-red-200' : isLow ? 'bg-amber-50 border-amber-200' : 'bg-white border-gray-200',
                            ].join(' ')}>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Estoque Atual</p>
                                <p className={['text-5xl font-black', isOut ? 'text-red-600' : isLow ? 'text-amber-500' : 'text-gray-900'].join(' ')}>
                                    {product.quantity}
                                </p>
                                <p className="text-sm text-gray-400 mt-1">unidades</p>
                                <div className="mt-3 pt-3 border-t border-gray-200/60">
                                    <p className="text-xs text-gray-500">
                                        Mínimo: <span className="font-semibold text-gray-700">{product.min_quantity} un.</span>
                                    </p>
                                </div>
                                {isOut && (
                                    <div className="mt-3 p-2.5 bg-red-100 rounded-lg">
                                        <p className="text-xs font-semibold text-red-700">⚠ Produto esgotado!</p>
                                    </div>
                                )}
                                {!isOut && isLow && (
                                    <div className="mt-3 p-2.5 bg-amber-100 rounded-lg">
                                        <p className="text-xs font-semibold text-amber-700">⚠ Estoque abaixo do mínimo</p>
                                    </div>
                                )}
                            </div>

                            {/* Metadata */}
                            <div className="bg-white rounded-xl border border-gray-200 shadow-xs p-5 space-y-3">
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Informações</p>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">ID</span>
                                        <span className="font-medium text-gray-700">#{product.id}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Slug</span>
                                        <span className="font-medium text-gray-700 truncate ml-4">{product.slug}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Criado em</span>
                                        <span className="font-medium text-gray-700">{formatDate(product.created_at)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Atualizado</span>
                                        <span className="font-medium text-gray-700">{formatDate(product.updated_at)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete modal */}
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
                <p className="text-sm text-gray-700">
                    Tem certeza que deseja excluir{' '}
                    <strong className="font-semibold">{product.name}</strong>?
                    Esta ação não pode ser desfeita.
                </p>
            </Modal>
        </AdminLayout>
    );
}
