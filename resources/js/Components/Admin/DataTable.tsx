import React from 'react';
import { Link } from '@inertiajs/react';

/** Decode &laquo; / &raquo; / &hellip; HTML entities from Laravel's paginator labels. */
function decodePaginationLabel(label: string): string {
    return label
        .replace(/&laquo;\s*/g, '«\u00A0')
        .replace(/\s*&raquo;/g, '\u00A0»')
        .replace(/&hellip;/g, '…')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');
}

/** Return page numbers to show with null gaps representing ellipsis. */
function windowedPages(current: number, last: number): (number | null)[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    const pages: (number | null)[] = [1];
    if (current > 3) { pages.push(null); }
    for (let p = Math.max(2, current - 1); p <= Math.min(last - 1, current + 1); p++) {
        pages.push(p);
    }
    if (current < last - 2) { pages.push(null); }
    pages.push(last);
    return pages;
}

export interface Column<T> {
    key: keyof T | string;
    label: string;
    sortable?: boolean;
    render?: (row: T) => React.ReactNode;
    className?: string;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links?: Array<{ url: string | null; label: string; active: boolean }>;
}

interface DataTableProps<T extends { id: number | string }> {
    columns: Column<T>[];
    data: T[];
    pagination?: PaginationMeta;
    sortKey?: string;
    sortDir?: 'asc' | 'desc';
    onSort?: (key: string) => void;
    emptyMessage?: string;
    loading?: boolean;
    baseUrl?: string;
}

function SortIcon({ direction }: { direction?: 'asc' | 'desc' | null }) {
    if (!direction) {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-warm-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12z" />
                <path d="M15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
            </svg>
        );
    }
    return direction === 'asc' ? (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-kintsugi-500" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clipRule="evenodd" />
        </svg>
    ) : (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-kintsugi-500" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
        </svg>
    );
}

function getCellValue<T>(row: T, key: string): React.ReactNode {
    const keys = key.split('.');
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    let value: any = row;
    for (const k of keys) {
        value = value?.[k];
    }
    if (value === null || value === undefined) { return '—'; }
    return String(value);
}

export default function DataTable<T extends { id: number | string }>({
    columns,
    data,
    pagination,
    sortKey,
    sortDir,
    onSort,
    emptyMessage = 'Nenhum registro encontrado.',
    loading = false,
    baseUrl,
}: DataTableProps<T>) {
    return (
        <div className="bg-white rounded-xl border border-warm-200 shadow-xs overflow-hidden">
            {/* Table */}
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-warm-200 bg-warm-50">
                            {columns.map((col) => (
                                <th
                                    key={String(col.key)}
                                    scope="col"
                                    className={[
                                        'px-4 py-3 text-left text-xs font-semibold text-warm-500 uppercase tracking-wider whitespace-nowrap',
                                        col.className ?? '',
                                        col.sortable && onSort ? 'cursor-pointer select-none hover:text-warm-600' : '',
                                    ].join(' ')}
                                    onClick={() => col.sortable && onSort?.(String(col.key))}
                                >
                                    <span className="flex items-center gap-1.5">
                                        {col.label}
                                        {col.sortable && (
                                            <SortIcon
                                                direction={sortKey === col.key ? (sortDir ?? null) : null}
                                            />
                                        )}
                                    </span>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-warm-200">
                        {loading ? (
                            Array.from({ length: 5 }).map((_, i) => (
                                <tr key={i}>
                                    {columns.map((col) => (
                                        <td key={String(col.key)} className="px-4 py-3">
                                            <div className="h-4 bg-warm-100 rounded motion-safe:animate-pulse w-3/4" />
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-4 py-12 text-center text-warm-400"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row) => (
                                <tr
                                    key={row.id}
                                    className="hover:bg-warm-50 transition-colors"
                                >
                                    {columns.map((col) => (
                                        <td
                                            key={String(col.key)}
                                            className={['px-4 py-3 text-warm-600', col.className ?? ''].join(' ')}
                                        >
                                            {col.render
                                                ? col.render(row)
                                                : getCellValue(row, String(col.key))}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
                <div className="flex items-center justify-between px-4 py-3 border-t border-warm-200 bg-warm-50">
                    <p className="text-sm text-warm-500">
                        Mostrando{' '}
                        <span className="font-medium text-warm-600">
                            {(pagination.current_page - 1) * pagination.per_page + 1}–
                            {Math.min(pagination.current_page * pagination.per_page, pagination.total)}
                        </span>{' '}
                        de <span className="font-medium text-warm-600">{pagination.total}</span> registros
                    </p>
                    <div className="flex items-center gap-1">
                        {pagination.links
                            ? pagination.links.map((link, idx) => {
                                const isDisabled = !link.url;
                                const isActive = link.active;
                                const label = decodePaginationLabel(link.label);
                                return isDisabled ? (
                                    <span
                                        key={idx}
                                        className="px-2.5 py-1.5 text-xs rounded text-warm-400 cursor-default"
                                    >
                                        {label}
                                    </span>
                                ) : (
                                    <Link
                                        key={idx}
                                        href={link.url!}
                                        className={[
                                            'px-2.5 py-1.5 text-xs rounded transition-colors',
                                            isActive
                                                ? 'bg-kintsugi-600 text-white font-semibold'
                                                : 'text-warm-600 hover:bg-warm-100',
                                        ].join(' ')}
                                    >
                                        {label}
                                    </Link>
                                );
                            })
                            : windowedPages(pagination.current_page, pagination.last_page).map((page, idx) => {
                                if (page === null) {
                                    return (
                                        <span key={`gap-${idx}`} className="px-1.5 py-1.5 text-xs text-warm-400 cursor-default">
                                            …
                                        </span>
                                    );
                                }
                                const isActive = page === pagination.current_page;
                                const href = baseUrl ? `${baseUrl}?page=${page}` : `?page=${page}`;
                                return isActive ? (
                                    <span
                                        key={page}
                                        className="px-2.5 py-1.5 text-xs rounded bg-kintsugi-600 text-white font-semibold"
                                        aria-current="page"
                                    >
                                        {page}
                                    </span>
                                ) : (
                                    <Link
                                        key={page}
                                        href={href}
                                        className="px-2.5 py-1.5 text-xs rounded text-warm-600 hover:bg-warm-100 transition-colors"
                                    >
                                        {page}
                                    </Link>
                                );
                            })}
                    </div>
                </div>
            )}
        </div>
    );
}
