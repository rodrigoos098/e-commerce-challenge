import React, { useEffect, useRef } from 'react';

interface SearchInputProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    autoFocus?: boolean;
}

export default function SearchInput({
    value,
    onChange,
    placeholder = 'Buscar produtos...',
    className = '',
    autoFocus = false,
}: SearchInputProps) {
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (autoFocus && inputRef.current) {
            inputRef.current.focus();
        }
    }, [autoFocus]);

    return (
        <div className={`relative ${className}`}>
            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-4 w-4 text-warm-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={1.8}
                    aria-hidden="true"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input
                ref={inputRef}
                type="search"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className="w-full rounded-xl border border-warm-200 bg-white py-2.5 pl-10 pr-10 text-sm text-warm-700 placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all"
            />
            {value && (
                <button
                    type="button"
                    onClick={() => onChange('')}
                    aria-label="Limpar busca"
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
