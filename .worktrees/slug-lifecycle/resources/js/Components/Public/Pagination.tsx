import React from 'react';

interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

interface PaginationProps {
    meta: PaginationMeta;
    links?: PaginationLinks;
    onPageChange: (page: number) => void;
}

function getPageNumbers(current: number, last: number): (number | '...')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const pages: (number | '...')[] = [];

    if (current <= 4) {
        pages.push(1, 2, 3, 4, 5, '...', last);
    } else if (current >= last - 3) {
        pages.push(1, '...', last - 4, last - 3, last - 2, last - 1, last);
    } else {
        pages.push(1, '...', current - 1, current, current + 1, '...', last);
    }

    return pages;
}

export default function Pagination({ meta, onPageChange }: PaginationProps) {
    const { current_page, last_page, total, per_page } = meta;

    if (last_page <= 1) { return null; }

    const pages = getPageNumbers(current_page, last_page);
    const startItem = (current_page - 1) * per_page + 1;
    const endItem = Math.min(current_page * per_page, total);

    const btnBase = 'flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-colors duration-150 border';
    const btnActive = 'bg-kintsugi-500 text-white border-kintsugi-500 shadow-sm';
    const btnInactive = 'border-warm-200 text-warm-600 hover:bg-warm-50 hover:text-kintsugi-500 hover:border-kintsugi-200';
    const btnDisabled = 'border-warm-200 text-warm-300 cursor-not-allowed';

    return (
        <nav aria-label="Paginação" className="flex flex-col sm:flex-row items-center justify-between gap-4 mt-8">
            {/* Info */}
            <p className="text-sm text-warm-500">
                Mostrando <span className="font-semibold text-warm-700">{startItem}–{endItem}</span> de{' '}
                <span className="font-semibold text-warm-700">{total}</span> resultados
            </p>

            {/* Pages */}
            <div className="flex items-center gap-1">
                {/* Prev */}
                <button
                    type="button"
                    onClick={() => onPageChange(current_page - 1)}
                    disabled={current_page === 1}
                    aria-label="Página anterior"
                    className={`${btnBase} ${current_page === 1 ? btnDisabled : btnInactive}`}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                {/* Numbers */}
                {pages.map((page, idx) =>
                    page === '...' ? (
                        <span key={`ellipsis-${idx}`} className="flex h-9 w-9 items-center justify-center text-warm-400 text-sm">
                            …
                        </span>
                    ) : (
                        <button
                            key={page}
                            type="button"
                            onClick={() => onPageChange(page as number)}
                            aria-label={`Ir para página ${page}`}
                            aria-current={page === current_page ? 'page' : undefined}
                            className={`${btnBase} ${page === current_page ? btnActive : btnInactive}`}
                        >
                            {page}
                        </button>
                    ),
                )}

                {/* Next */}
                <button
                    type="button"
                    onClick={() => onPageChange(current_page + 1)}
                    disabled={current_page === last_page}
                    aria-label="Próxima página"
                    className={`${btnBase} ${current_page === last_page ? btnDisabled : btnInactive}`}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </nav>
    );
}
