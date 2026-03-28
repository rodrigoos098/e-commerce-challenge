import React from 'react';

interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface PaginationProps {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
}

type PageItem = number | 'ellipsis-start' | 'ellipsis-end';

/**
 * Returns a windowed list of page items with ellipsis.
 * Example (current=1, last=41): [1, 2, 3, 4, 5, 'ellipsis-end', 41]
 * Example (current=20, last=41): [1, 'ellipsis-start', 18, 19, 20, 21, 22, 'ellipsis-end', 41]
 * Example (current=41, last=41): [1, 'ellipsis-start', 37, 38, 39, 40, 41]
 */
function getPageItems(current: number, last: number): PageItem[] {
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  const delta = 2;
  const rangeStart = Math.max(2, current - delta);
  const rangeEnd = Math.min(last - 1, current + delta);

  const items: PageItem[] = [1];

  if (rangeStart > 2) {
    items.push('ellipsis-start');
  }

  for (let p = rangeStart; p <= rangeEnd; p++) {
    items.push(p);
  }

  if (rangeEnd < last - 1) {
    items.push('ellipsis-end');
  }

  items.push(last);

  return items;
}

export default function Pagination({ meta, onPageChange }: PaginationProps) {
  const { current_page, last_page, total, per_page } = meta;

  if (last_page <= 1) {
    return null;
  }

  const pageItems = getPageItems(current_page, last_page);
  const startItem = (current_page - 1) * per_page + 1;
  const endItem = Math.min(current_page * per_page, total);

  const btnBase =
    'flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-colors duration-150 border';
  const btnActive = 'bg-kintsugi-500 text-white border-kintsugi-500 shadow-sm';
  const btnInactive =
    'border-warm-200 text-warm-600 hover:bg-warm-50 hover:text-kintsugi-500 hover:border-kintsugi-200';
  const btnDisabled = 'border-warm-200 text-warm-300 cursor-not-allowed';

  return (
    <nav
      aria-label="Paginação"
      className="flex flex-col sm:flex-row items-center justify-between gap-4 mt-8"
    >
      {/* Info */}
      <p className="text-sm text-warm-500">
        Mostrando{' '}
        <span className="font-semibold text-warm-700">
          {startItem}–{endItem}
        </span>{' '}
        de <span className="font-semibold text-warm-700">{total}</span> resultados · Página{' '}
        <span className="font-semibold text-warm-700">{current_page}</span> de{' '}
        <span className="font-semibold text-warm-700">{last_page}</span>
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
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2}
            aria-hidden="true"
          >
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        {/* Numbers + ellipsis */}
        {pageItems.map((item) => {
          if (item === 'ellipsis-start' || item === 'ellipsis-end') {
            return (
              <span
                key={item}
                className="flex h-9 w-9 items-center justify-center text-sm text-warm-400 select-none"
                aria-hidden="true"
              >
                …
              </span>
            );
          }

          return (
            <button
              key={item}
              type="button"
              onClick={() => onPageChange(item)}
              aria-label={`Ir para página ${item}`}
              aria-current={item === current_page ? 'page' : undefined}
              className={`${btnBase} ${item === current_page ? btnActive : btnInactive}`}
            >
              {item}
            </button>
          );
        })}

        {/* Next */}
        <button
          type="button"
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page === last_page}
          aria-label="Próxima página"
          className={`${btnBase} ${current_page === last_page ? btnDisabled : btnInactive}`}
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2}
            aria-hidden="true"
          >
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </nav>
  );
}
