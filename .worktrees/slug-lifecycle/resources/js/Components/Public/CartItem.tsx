import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { formatPrice } from '@/utils/format';
import QuantitySelector from '@/Components/Public/QuantitySelector';
import type { CartItem as CartItemType } from '@/types/public';

interface CartItemProps {
    item: CartItemType;
    onUpdate?: (itemId: number, quantity: number) => void;
    onRemove?: (itemId: number) => void;
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
        <div className="flex items-start gap-4 py-5 border-b border-warm-200 last:border-0">
            {/* Image */}
            <div className="shrink-0 h-20 w-20 rounded-xl overflow-hidden bg-warm-100 border border-warm-200">
                <img
                    src={`/storage/products/${item.product.id}.webp`}
                    alt={item.product.name}
                    className="h-full w-full object-cover"
                    loading="lazy"
                    onError={(e) => {
                        const target = e.currentTarget;
                        target.style.display = 'none';
                        const fallback = target.parentElement?.querySelector('.cart-placeholder');
                        if (fallback) { (fallback as HTMLElement).style.display = 'flex'; }
                    }}
                />
                <div className="cart-placeholder hidden h-full w-full items-center justify-center" style={{ display: 'none' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8 text-warm-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zM8.25 8.25h.008v.008H8.25V8.25z" />
                    </svg>
                </div>
            </div>

            {/* Info */}
            <div className="flex flex-1 flex-col min-w-0">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h4 className="text-sm font-semibold text-warm-700 truncate">{item.product.name}</h4>
                        {item.product.category && (
                            <p className="text-xs text-warm-500 mt-0.5 truncate">{item.product.category.name}</p>
                        )}
                    </div>
                    {/* Remove */}
                    <button
                        type="button"
                        onClick={handleRemove}
                        aria-label={`Remover ${item.product.name} do carrinho`}
                        className="shrink-0 flex items-center justify-center min-h-[44px] min-w-[44px] text-warm-500 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
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
                        <p className="text-sm font-bold text-warm-700">{formatPrice(lineTotal)}</p>
                        <p className="text-xs text-warm-500">{formatPrice(item.product.price)} / unid.</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
