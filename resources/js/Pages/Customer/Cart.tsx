import React from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import PublicLayout from '@/Layouts/PublicLayout';
import CartItemComponent from '@/Components/Public/CartItem';
import type { CartPageProps, Cart } from '@/types/public';

// ——— Mock ————————————————————————————————————————————————

const MOCK_CART: Cart = {
    id: 1,
    items: [
        {
            id: 1,
            product: {
                id: 1,
                name: 'Fone de Ouvido Bluetooth Premium',
                slug: 'fone-bluetooth',
                description: '',
                price: 299.9,
                quantity: 50,
                min_quantity: 5,
                active: true,
                category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null },
                tags: [],
                created_at: '',
                updated_at: '',
            },
            quantity: 2,
        },
        {
            id: 2,
            product: {
                id: 4,
                name: 'Smart Watch Series X',
                slug: 'smart-watch',
                description: '',
                price: 799.9,
                quantity: 15,
                min_quantity: 5,
                active: true,
                category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null },
                tags: [],
                created_at: '',
                updated_at: '',
            },
            quantity: 1,
        },
    ],
    subtotal: 1399.7,
    tax: 125.97,
    shipping_cost: 15.0,
    total: 1540.67,
    item_count: 3,
};

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export default function Cart({ cart }: Partial<CartPageProps>) {
    const c = cart ?? MOCK_CART;

    const handleClearCart = () => {
        router.delete('/cart', {
            onSuccess: () => toast.success('Carrinho limpo!'),
            onError: () => toast.error('Erro ao limpar carrinho.'),
        });
    };

    if (c.items.length === 0) {
        return (
            <PublicLayout title="Carrinho">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-20 text-center">
                    <div className="flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">Seu carrinho está vazio</h1>
                    <p className="text-gray-500 mb-8">Adicione produtos para começar a comprar.</p>
                    <Link
                        href="/products"
                        className="inline-flex items-center gap-2 rounded-2xl bg-violet-600 px-8 py-3 text-sm font-bold text-white hover:bg-violet-700 transition-colors shadow-lg"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Ver Produtos
                    </Link>
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout title="Carrinho">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900">
                        Meu Carrinho
                        <span className="ml-2 text-base font-normal text-gray-400">({c.item_count} {c.item_count === 1 ? 'item' : 'itens'})</span>
                    </h1>
                    <button
                        type="button"
                        onClick={handleClearCart}
                        className="text-sm text-red-500 hover:text-red-700 transition-colors font-medium"
                    >
                        Limpar carrinho
                    </button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Items */}
                    <div className="lg:col-span-2">
                        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm px-6">
                            {c.items.map((item) => (
                                <CartItemComponent key={item.id} item={item} />
                            ))}
                        </div>

                        <div className="mt-4">
                            <Link
                                href="/products"
                                className="inline-flex items-center gap-2 text-sm text-violet-600 hover:text-violet-800 font-medium transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Continuar comprando
                            </Link>
                        </div>
                    </div>

                    {/* Summary */}
                    <div className="lg:col-span-1">
                        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sticky top-24">
                            <h2 className="text-lg font-bold text-gray-900 mb-5">Resumo do pedido</h2>

                            <div className="space-y-3 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>{formatPrice(c.subtotal)}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Impostos</span>
                                    <span>{formatPrice(c.tax)}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Frete</span>
                                    <span className={c.shipping_cost === 0 ? 'text-green-600 font-medium' : ''}>
                                        {c.shipping_cost === 0 ? 'Grátis' : formatPrice(c.shipping_cost)}
                                    </span>
                                </div>
                                <div className="border-t border-gray-100 pt-3 flex justify-between font-bold text-base text-gray-900">
                                    <span>Total</span>
                                    <span>{formatPrice(c.total)}</span>
                                </div>
                            </div>

                            <Link
                                href="/customer/checkout"
                                className="mt-6 block w-full rounded-2xl bg-violet-600 py-3.5 text-center text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-lg shadow-violet-200"
                            >
                                Finalizar Compra
                            </Link>

                            <div className="mt-4 flex items-center justify-center gap-4 text-xs text-gray-400">
                                <span>🔒 Compra segura</span>
                                <span>•</span>
                                <span>✓ SSL</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
