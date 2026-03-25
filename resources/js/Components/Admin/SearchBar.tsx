import React, { useState, useEffect, useRef } from 'react';

interface SearchBarProps {
    onSearch: (value: string) => void;
    placeholder?: string;
    initialValue?: string;
    debounceMs?: number;
    className?: string;
}

export default function SearchBar({
    onSearch,
    placeholder = 'Pesquisar...',
    initialValue = '',
    debounceMs = 350,
    className = '',
}: SearchBarProps) {
    const [value, setValue] = useState(initialValue);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        setValue(initialValue);
    }, [initialValue]);

    // Clear pending debounce timer on unmount
    useEffect(() => {
        return () => {
            if (timerRef.current) { clearTimeout(timerRef.current); }
        };
    }, []);

    function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
        const newVal = e.target.value;
        setValue(newVal);

        if (timerRef.current) {
            clearTimeout(timerRef.current);
        }
        timerRef.current = setTimeout(() => {
            onSearch(newVal);
        }, debounceMs);
    }

    function handleClear() {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        setValue('');
        onSearch('');
    }

    return (
        <div className={['relative', className].join(' ')}>
            {/* Search icon */}
            <div className="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-warm-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input
                type="search"
                value={value}
                onChange={handleChange}
                placeholder={placeholder}
                aria-label={placeholder}
                className="w-full rounded-lg border border-warm-300 bg-white py-2 pl-9 pr-9 text-sm text-warm-700 placeholder-warm-400 focus:border-kintsugi-500 focus:outline-none focus:ring-2 focus:ring-kintsugi-500/20 transition-colors"
            />

            {/* Clear button */}
            {value && (
                <button
                    type="button"
                    onClick={handleClear}
                    aria-label="Limpar pesquisa"
                    className="absolute inset-y-0 right-3 flex items-center text-warm-400 hover:text-warm-600 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            )}
        </div>
    );
}
