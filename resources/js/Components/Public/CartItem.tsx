import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import QuantitySelector from '@/Components/Public/QuantitySelector';
import type { CartItem as CartItemType } from '@/types/public';

interface CartItemProps {
    item: CartItemType;
    onUpdate?: (itemId: number, quantity: number) => void;
    onRemove?: (itemId: number) => void;
}

function formatPrice(value: number): string {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export default function CartItem({ item, onUpdate, onRemove }: CartItemProps) {
    const [quantity, setQuantity] = useState(item.quantity);
    const [loading, setLoading] = useState(false);

    const handleQuantityChange = (newQty: number) => {
        setQuantity(newQty);
        setLoading(true);

        if (onUpdate) {
            onUpdate(item.id, newQty);
            setLoading(false);
            return;
        }

        router.put(
            `/cart/items/${item.id}`,
            { quantity: newQty },
            {
                preserveScroll: true,
                onSuccess: () => setLoading(false),
                onError: () => {
                    setQuantity(item.quantity);
                    setLoading(false);
                    toast.error('Erro ao atualizar quantidade.');
                },
            },
        );
    };

    const handleRemove = () => {
        if (onRemove) {
            onRemove(item.id);
            return;
        }

        router.delete(`/cart/items/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Item removido do carrinho.'),
            onError: () => toast.error('Erro ao remover item.'),
        });
    };

    const lineTotal = item.product.price * quantity;

    return (
        <div className="flex items-start gap-4 py-5 border-b border-gray-100 last:border-0">
            {/* Image */}
            <div className="shrink-0 h-20 w-20 rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                <img
                    src={`https://picsum.photos/seed/${item.product.id}/160/160`}
                    alt={item.product.name}
                    className="h-full w-full object-cover"
                    loading="lazy"
                />
            </div>

            {/* Info */}
            <div className="flex flex-1 flex-col min-w-0">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h4 className="text-sm font-semibold text-gray-900 truncate">{item.product.name}</h4>
                        {item.product.category && (
                            <p className="text-xs text-gray-400 mt-0.5">{item.product.category.name}</p>
                        )}
                    </div>
                    {/* Remove */}
                    <button
                        type="button"
                        onClick={handleRemove}
                        aria-label={`Remover ${item.product.name} do carrinho`}
                        className="shrink-0 text-gray-300 hover:text-red-500 transition-colors p-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="mt-3 flex items-center justify-between gap-3">
                    <QuantitySelector
                        value={quantity}
                        onChange={handleQuantityChange}
                        max={item.product.quantity}
                        disabled={loading}
                    />
                    <div className="text-right">
                        <p className="text-sm font-bold text-gray-900">{formatPrice(lineTotal)}</p>
                        <p className="text-xs text-gray-400">{formatPrice(item.product.price)} each</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
