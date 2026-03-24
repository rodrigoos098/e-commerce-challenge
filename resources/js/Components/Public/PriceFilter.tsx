import React, { useState, useCallback } from 'react';

interface PriceFilterProps {
    min: number;
    max: number;
    currentMin: number;
    currentMax: number;
    onChange: (min: number, max: number) => void;
}

function formatPrice(value: number): string {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export default function PriceFilter({ min, max, currentMin, currentMax, onChange }: PriceFilterProps) {
    const [localMin, setLocalMin] = useState(currentMin);
    const [localMax, setLocalMax] = useState(currentMax);

    const handleMinChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const val = Math.min(Number(e.target.value), localMax - 1);
            setLocalMin(val);
        },
        [localMax],
    );

    const handleMaxChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const val = Math.max(Number(e.target.value), localMin + 1);
            setLocalMax(val);
        },
        [localMin],
    );

    const handleApply = () => {
        onChange(localMin, localMax);
    };

    const handleReset = () => {
        setLocalMin(min);
        setLocalMax(max);
        onChange(min, max);
    };

    return (
        <div>
            <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-warm-500">Faixa de Preço</h3>

            {/* Price display */}
            <div className="mb-4 flex items-center justify-between text-sm text-warm-600">
                <span className="font-medium">{formatPrice(localMin)}</span>
                <span className="text-warm-400">até</span>
                <span className="font-medium">{formatPrice(localMax)}</span>
            </div>

            {/* Min slider */}
            <div className="space-y-3">
                <div className="relative">
                    <label className="sr-only">Preço mínimo</label>
                    <input
                        type="range"
                        min={min}
                        max={max}
                        step={10}
                        value={localMin}
                        onChange={handleMinChange}
                        className="w-full h-1.5 rounded-full appearance-none bg-gray-200 accent-kintsugi-500 cursor-pointer"
                    />
                </div>

                {/* Max slider */}
                <div className="relative">
                    <label className="sr-only">Preço máximo</label>
                    <input
                        type="range"
                        min={min}
                        max={max}
                        step={10}
                        value={localMax}
                        onChange={handleMaxChange}
                        className="w-full h-1.5 rounded-full appearance-none bg-gray-200 accent-kintsugi-500 cursor-pointer"
                    />
                </div>
            </div>

            {/* Actions */}
            <div className="mt-4 flex gap-2">
                <button
                    type="button"
                    onClick={handleApply}
                    className="flex-1 rounded-lg bg-kintsugi-500 py-1.5 text-xs font-semibold text-white hover:bg-kintsugi-600 transition-colors"
                >
                    Aplicar
                </button>
                <button
                    type="button"
                    onClick={handleReset}
                    className="flex-1 rounded-lg border border-warm-200 py-1.5 text-xs font-medium text-warm-600 hover:bg-warm-50 transition-colors"
                >
                    Limpar
                </button>
            </div>
        </div>
    );
}
