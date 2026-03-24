import React from 'react';
import Spinner from './Spinner';

type Variant = 'primary' | 'secondary' | 'danger';
type Size = 'sm' | 'md';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    loading?: boolean;
}

const variantClasses: Record<Variant, string> = {
    primary:
        'text-white bg-kintsugi-600 hover:bg-kintsugi-700 shadow-sm',
    secondary:
        'text-warm-600 bg-white border border-warm-300 hover:bg-warm-50',
    danger:
        'text-white bg-red-600 hover:bg-red-700 shadow-sm',
};

const sizeClasses: Record<Size, string> = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-5 py-2.5 text-sm',
};

export default function Button({
    variant = 'primary',
    size = 'md',
    loading = false,
    disabled,
    className = '',
    children,
    ...props
}: ButtonProps) {
    return (
        <button
            disabled={disabled || loading}
            className={[
                'inline-flex items-center gap-2 font-medium rounded-lg transition-colors',
                'disabled:opacity-60 disabled:cursor-not-allowed',
                variantClasses[variant],
                sizeClasses[size],
                className,
            ].join(' ')}
            {...props}
        >
            {loading && <Spinner />}
            {children}
        </button>
    );
}
