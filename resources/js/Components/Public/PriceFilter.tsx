import React, { useState, useCallback, useEffect } from 'react';
import { formatPrice } from '@/utils/format';

interface PriceFilterProps {
  min: number;
  max: number;
  currentMin: number;
  currentMax: number;
  onChange: (min: number, max: number) => void;
}

export default function PriceFilter({
  min,
  max,
  currentMin,
  currentMax,
  onChange,
}: PriceFilterProps) {
  const [localMin, setLocalMin] = useState(currentMin);
  const [localMax, setLocalMax] = useState(currentMax);

  const clampMin = useCallback(
    (value: number, nextMax: number): number => {
      return Math.max(min, Math.min(value, nextMax - 1));
    },
    [min]
  );

  const clampMax = useCallback(
    (value: number, nextMin: number): number => {
      return Math.min(max, Math.max(value, nextMin + 1));
    },
    [max]
  );

  // Sync state when props change externally (e.g. "clear all filters")
  useEffect(() => {
    setLocalMin(currentMin);
    setLocalMax(currentMax);
  }, [currentMin, currentMax]);

  const handleMinChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const val = clampMin(Number(e.target.value), localMax);
      setLocalMin(val);
    },
    [clampMin, localMax]
  );

  const handleMaxChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const val = clampMax(Number(e.target.value), localMin);
      setLocalMax(val);
    },
    [clampMax, localMin]
  );

  const handleMinInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.value === '') {
        setLocalMin(min);
        return;
      }

      setLocalMin(clampMin(Number(e.target.value), localMax));
    },
    [clampMin, localMax, min]
  );

  const handleMaxInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.value === '') {
        setLocalMax(max);
        return;
      }

      setLocalMax(clampMax(Number(e.target.value), localMin));
    },
    [clampMax, localMin, max]
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
      <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-warm-500">
        Faixa de Preço
      </h3>

      {/* Price display */}
      <div className="mb-4 flex items-center justify-between text-sm text-warm-600">
        <span className="font-medium">{formatPrice(localMin)}</span>
        <span className="text-warm-400">até</span>
        <span className="font-medium">{formatPrice(localMax)}</span>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3">
        <div className="space-y-1.5">
          <label htmlFor="price-min" className="text-xs font-medium text-warm-500">
            Mínimo
          </label>
          <input
            id="price-min"
            type="number"
            inputMode="numeric"
            min={min}
            max={localMax - 1}
            step={10}
            value={localMin}
            onChange={handleMinInputChange}
            className="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm text-warm-700 outline-none transition focus:border-kintsugi-400 focus:ring-2 focus:ring-kintsugi-100"
          />
        </div>

        <div className="space-y-1.5">
          <label htmlFor="price-max" className="text-xs font-medium text-warm-500">
            Máximo
          </label>
          <input
            id="price-max"
            type="number"
            inputMode="numeric"
            min={localMin + 1}
            max={max}
            step={10}
            value={localMax}
            onChange={handleMaxInputChange}
            className="w-full rounded-lg border border-warm-200 px-3 py-2 text-sm text-warm-700 outline-none transition focus:border-kintsugi-400 focus:ring-2 focus:ring-kintsugi-100"
          />
        </div>
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
            className="w-full h-1.5 rounded-full appearance-none bg-warm-200 accent-kintsugi-500 cursor-pointer"
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
            className="w-full h-1.5 rounded-full appearance-none bg-warm-200 accent-kintsugi-500 cursor-pointer"
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
