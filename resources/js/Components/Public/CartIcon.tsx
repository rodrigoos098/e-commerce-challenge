import React from 'react';
import { Link } from '@inertiajs/react';

interface CartIconProps {
    count: number;
    href?: string;
}

export default function CartIcon({ count, href = '/cart' }: CartIconProps) {
    return (
        <Link
            href={href}
            aria-label={`Carrinho com ${count} ${count === 1 ? 'item' : 'itens'}`}
            className="relative flex items-center justify-center p-2 text-warm-600 hover:text-kintsugi-500 transition-colors duration-150"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                />
            </svg>
            {count > 0 && (
                <span
                    className="absolute -top-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-kintsugi-500 text-xs font-bold text-white tabular-nums"
                    aria-hidden="true"
                >
                    {count > 99 ? '99+' : count}
                </span>
            )}
        </Link>
    );
}
