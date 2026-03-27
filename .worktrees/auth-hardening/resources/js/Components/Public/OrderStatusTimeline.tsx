import React from 'react';
import type { OrderStatus } from '@/types/public';

interface OrderStatusTimelineProps {
    status: OrderStatus;
}

interface Step {
    key: OrderStatus | 'shipped';
    label: string;
    description: string;
}

const STEPS: Step[] = [
    { key: 'pending', label: 'Aguardando', description: 'Pedido recebido' },
    { key: 'processing', label: 'Processando', description: 'Em preparação' },
    { key: 'shipped', label: 'Enviado', description: 'A caminho' },
    { key: 'delivered', label: 'Entregue', description: 'Concluído' },
];

const STATUS_ORDER: Record<OrderStatus, number> = {
    pending: 0,
    processing: 1,
    shipped: 2,
    delivered: 3,
    cancelled: -1,
};

export default function OrderStatusTimeline({ status }: OrderStatusTimelineProps) {
    if (status === 'cancelled') {
        return (
            <div className="flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 px-4 py-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <p className="text-sm font-semibold text-red-700">Pedido Cancelado</p>
                    <p className="text-xs text-red-500">Este pedido foi cancelado.</p>
                </div>
            </div>
        );
    }

    const currentIndex = STATUS_ORDER[status] ?? 0;

    return (
        <div role="group" aria-label="Status do pedido">
            <ol className="flex flex-col sm:flex-row gap-2 sm:gap-0">
                {STEPS.map((step, idx) => {
                    const isDone = idx < currentIndex;
                    const isCurrent = idx === currentIndex;
                    const isUpcoming = idx > currentIndex;

                    return (
                        <li key={step.key} className="flex flex-1 items-start sm:flex-col">
                            {/* Step + connector */}
                            <div className="flex sm:flex-col items-center gap-2 sm:gap-0 w-full sm:w-auto">
                                {/* Dot */}
                                <div
                                    className={`relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 transition-colors
                                        ${isCurrent ? 'border-kintsugi-500 bg-kintsugi-500 shadow-sm shadow-kintsugi-100' : ''}
                                        ${isDone ? 'border-kintsugi-500 bg-kintsugi-500' : ''}
                                        ${isUpcoming ? 'border-warm-200 bg-white' : ''}
                                    `}
                                    aria-current={isCurrent ? 'step' : undefined}
                                >
                                    {isDone ? (
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5} aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    ) : isCurrent ? (
                                        <span className="h-2 w-2 rounded-full bg-white motion-safe:animate-pulse" aria-hidden="true" />
                                    ) : (
                                        <span className="h-2 w-2 rounded-full bg-warm-300" aria-hidden="true" />
                                    )}
                                </div>

                                {/* Line */}
                                {idx < STEPS.length - 1 && (
                                    <div
                                        className={`sm:hidden h-8 w-0.5 ml-3.5 ${isDone ? 'bg-kintsugi-500' : 'bg-warm-200'}`}
                                        aria-hidden="true"
                                    />
                                )}
                                {idx < STEPS.length - 1 && (
                                    <div
                                        className={`hidden sm:block flex-1 h-0.5 mx-1 mt-4 ${isDone ? 'bg-kintsugi-500' : 'bg-warm-200'}`}
                                        style={{ minWidth: 20 }}
                                        aria-hidden="true"
                                    />
                                )}
                            </div>

                            {/* Labels */}
                            <div className="ml-3 sm:ml-0 sm:mt-2 sm:text-center">
                                <p className={`text-xs font-semibold ${isCurrent ? 'text-kintsugi-600' : isDone ? 'text-warm-600' : 'text-warm-400'}`}>
                                    {step.label}
                                </p>
                                <p className="text-xs text-warm-400 hidden sm:block">{step.description}</p>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
