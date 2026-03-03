import React from 'react';
import type { OrderStatus } from '@/types/admin';

interface StatusBadgeProps {
    status: OrderStatus;
    size?: 'sm' | 'md';
}

const statusConfig: Record<OrderStatus, { label: string; classes: string }> = {
    pending: {
        label: 'Pendente',
        classes: 'bg-amber-100 text-amber-700 ring-amber-200',
    },
    processing: {
        label: 'Processando',
        classes: 'bg-blue-100 text-blue-700 ring-blue-200',
    },
    shipped: {
        label: 'Enviado',
        classes: 'bg-indigo-100 text-indigo-700 ring-indigo-200',
    },
    delivered: {
        label: 'Entregue',
        classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    },
    cancelled: {
        label: 'Cancelado',
        classes: 'bg-red-100 text-red-700 ring-red-200',
    },
};

const dotColor: Record<OrderStatus, string> = {
    pending: 'bg-amber-500',
    processing: 'bg-blue-500',
    shipped: 'bg-indigo-500',
    delivered: 'bg-emerald-500',
    cancelled: 'bg-red-500',
};

export default function StatusBadge({ status, size = 'md' }: StatusBadgeProps) {
    const config = statusConfig[status] ?? {
        label: status,
        classes: 'bg-gray-100 text-gray-600 ring-gray-200',
    };
    const dot = dotColor[status] ?? 'bg-gray-400';

    return (
        <span
            className={[
                'inline-flex items-center gap-1.5 rounded-full font-medium ring-1',
                config.classes,
                size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
            ].join(' ')}
        >
            <span className={['rounded-full flex-shrink-0', dot, size === 'sm' ? 'h-1.5 w-1.5' : 'h-2 w-2'].join(' ')} />
            {config.label}
        </span>
    );
}
