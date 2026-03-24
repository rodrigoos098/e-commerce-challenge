import React, { useEffect } from 'react';

interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    children: React.ReactNode;
    onConfirm?: () => void;
    confirmLabel?: string;
    cancelLabel?: string;
    confirmDestructive?: boolean;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg' | 'xl';
}

const sizeMap = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-2xl',
};

export default function Modal({
    isOpen,
    onClose,
    title,
    children,
    onConfirm,
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    confirmDestructive = false,
    loading = false,
    size = 'md',
}: ModalProps) {
    // Close on Escape key
    useEffect(() => {
        if (!isOpen) { return; }
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') { onClose(); }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [isOpen, onClose]);

    // Prevent body scroll
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => { document.body.style.overflow = ''; };
    }, [isOpen]);

    if (!isOpen) { return null; }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Panel */}
            <div
                className={[
                    'relative w-full bg-white rounded-xl shadow-xl border border-warm-200 flex flex-col max-h-[90vh]',
                    sizeMap[size],
                ].join(' ')}
            >
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-warm-200 flex-shrink-0">
                    <h2 id="modal-title" className="text-base font-semibold text-warm-700">
                        {title}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-warm-400 hover:text-warm-600 transition-colors rounded-md p-1 hover:bg-gray-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Content */}
                <div className="px-6 py-4 overflow-y-auto flex-1">
                    {children}
                </div>

                {/* Footer (only when onConfirm provided) */}
                {onConfirm && (
                    <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-warm-200 bg-warm-50 rounded-b-xl flex-shrink-0">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={loading}
                            className="px-4 py-2 text-sm font-medium text-warm-600 bg-white border border-warm-300 rounded-lg hover:bg-warm-50 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                        >
                            {cancelLabel}
                        </button>
                        <button
                            type="button"
                            onClick={onConfirm}
                            disabled={loading}
                            className={[
                                'px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2',
                                confirmDestructive
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : 'bg-kintsugi-600 hover:bg-kintsugi-700',
                            ].join(' ')}
                        >
                            {loading && (
                                <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            )}
                            {confirmLabel}
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
