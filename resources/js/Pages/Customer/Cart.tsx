import React from 'react';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import PublicLayout from '@/Layouts/PublicLayout';
import CartItemComponent from '@/Components/Public/CartItem';
import type { CartPageProps } from '@/types/public';

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export default function Cart({ cart }: CartPageProps) {
    const handleClearCart = () => {
        router.delete('/cart', {
            onSuccess: () => toast.success('Carrinho limpo!'),
            onError: () => toast.error('Erro ao limpar carrinho.'),
        });
    };

    if (cart.items.length === 0) {
        return (
            <PublicLayout title="Carrinho">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-20 text-center">
                    <div className="flex h-24 w-24 items-center justify-center rounded-full bg-warm-50 mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12 text-warm-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-warm-700 mb-2">Sua sacola esta vazia</h1>
                    <p className="text-warm-500 mb-8">Descubra pecas artesanais unicas para adicionar.</p>
                    <Link
                        href="/products"
                        className="inline-flex items-center gap-2 rounded-2xl bg-kintsugi-500 px-8 py-3 text-sm font-bold text-white hover:bg-kintsugi-600 transition-colors shadow-lg"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Explorar Colecao
                    </Link>
                </div>
            </PublicLayout>
        );
    }

    return (
        <PublicLayout title="Carrinho">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl sm:text-3xl font-extrabold text-warm-700">
                        Sua Sacola
                        <span className="ml-2 text-base font-normal text-warm-400">({cart.item_count} {cart.item_count === 1 ? 'item' : 'itens'})</span>
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
                        <div className="rounded-2xl bg-white border border-warm-200 shadow-sm px-6">
                            {cart.items.map((item) => (
                                <CartItemComponent key={item.id} item={item} />
                            ))}
                        </div>

                        <div className="mt-4">
                            <Link
                                href="/products"
                                className="inline-flex items-center gap-2 text-sm text-kintsugi-500 hover:text-kintsugi-600 font-medium transition-colors"
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
                        <div className="rounded-2xl bg-white border border-warm-200 shadow-sm p-6 sticky top-24">
                            <h2 className="text-lg font-bold text-warm-700 mb-5">Resumo do pedido</h2>

                            <div className="space-y-3 text-sm">
                                <div className="flex justify-between text-warm-600">
                                    <span>Subtotal</span>
                                    <span>{formatPrice(cart.subtotal)}</span>
                                </div>
                                <div className="flex justify-between text-warm-600">
                                    <span>Impostos</span>
                                    <span>{formatPrice(cart.tax)}</span>
                                </div>
                                <div className="flex justify-between text-warm-600">
                                    <span>Frete</span>
                                    <span className={cart.shipping_cost === 0 ? 'text-green-600 font-medium' : ''}>
                                        {cart.shipping_cost === 0 ? 'Grátis' : formatPrice(cart.shipping_cost)}
                                    </span>
                                </div>
                                <div className="border-t border-warm-200 pt-3 flex justify-between font-bold text-base text-warm-700">
                                    <span>Total</span>
                                    <span>{formatPrice(cart.total)}</span>
                                </div>
                            </div>

                            <Link
                                href="/customer/checkout"
                                className="mt-6 block w-full rounded-2xl bg-kintsugi-500 py-3.5 text-center text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-sm"
                            >
                                Finalizar Compra
                            </Link>

                            <div className="mt-4 flex items-center justify-center gap-1.5 text-xs text-warm-400">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <span>Compra segura com SSL</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
