import React from 'react';

interface QuantitySelectorProps {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    disabled?: boolean;
}

export default function QuantitySelector({
    value,
    onChange,
    min = 1,
    max = 999,
    disabled = false,
}: QuantitySelectorProps) {
    const handleDecrement = () => {
        if (value > min) { onChange(value - 1); }
    };

    const handleIncrement = () => {
        if (value < max) { onChange(value + 1); }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const parsed = parseInt(e.target.value, 10);
        if (!isNaN(parsed)) {
            onChange(Math.min(max, Math.max(min, parsed)));
        }
    };

    const btnBase = `flex h-8 w-8 items-center justify-center rounded-lg transition-colors duration-150
        text-gray-600 hover:bg-gray-100 hover:text-gray-900 active:scale-90
        disabled:opacity-40 disabled:cursor-not-allowed`;

    return (
        <div className="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1" role="group" aria-label="Selecionar quantidade">
            <button
                type="button"
                onClick={handleDecrement}
                disabled={disabled || value <= min}
                aria-label="Diminuir quantidade"
                className={btnBase}
            >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5} aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 12H4" />
                </svg>
            </button>

            <input
                type="number"
                value={value}
                onChange={handleInputChange}
                min={min}
                max={max}
                disabled={disabled}
                aria-label="Quantidade"
                className="w-10 text-center text-sm font-semibold text-gray-900 bg-transparent border-0 focus:outline-none disabled:opacity-40 [-moz-appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
            />

            <button
                type="button"
                onClick={handleIncrement}
                disabled={disabled || value >= max}
                aria-label="Aumentar quantidade"
                className={btnBase}
            >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5} aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    );
}
