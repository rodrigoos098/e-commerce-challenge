import React from 'react';
import { Link } from '@inertiajs/react';

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
            <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12z" />
                <path d="M15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
            </svg>
        );
    }
    return direction === 'asc' ? (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clipRule="evenodd" />
        </svg>
    ) : (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
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
        <div className="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            {/* Table */}
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-200 bg-gray-50">
                            {columns.map((col) => (
                                <th
                                    key={String(col.key)}
                                    scope="col"
                                    className={[
                                        'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap',
                                        col.className ?? '',
                                        col.sortable && onSort ? 'cursor-pointer select-none hover:text-gray-700' : '',
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
                    <tbody className="divide-y divide-gray-100">
                        {loading ? (
                            Array.from({ length: 5 }).map((_, i) => (
                                <tr key={i}>
                                    {columns.map((col) => (
                                        <td key={String(col.key)} className="px-4 py-3">
                                            <div className="h-4 bg-gray-100 rounded animate-pulse w-3/4" />
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-4 py-12 text-center text-gray-400"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row) => (
                                <tr
                                    key={row.id}
                                    className="hover:bg-gray-50 transition-colors"
                                >
                                    {columns.map((col) => (
                                        <td
                                            key={String(col.key)}
                                            className={['px-4 py-3 text-gray-700', col.className ?? ''].join(' ')}
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
                <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <p className="text-sm text-gray-500">
                        Mostrando{' '}
                        <span className="font-medium text-gray-700">
                            {(pagination.current_page - 1) * pagination.per_page + 1}–
                            {Math.min(pagination.current_page * pagination.per_page, pagination.total)}
                        </span>{' '}
                        de <span className="font-medium text-gray-700">{pagination.total}</span> registros
                    </p>
                    <div className="flex items-center gap-1">
                        {pagination.links
                            ? pagination.links.map((link, idx) => {
                                const isDisabled = !link.url;
                                const isActive = link.active;
                                return isDisabled ? (
                                    <span
                                        key={idx}
                                        className="px-2.5 py-1.5 text-xs rounded text-gray-400 cursor-default"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <Link
                                        key={idx}
                                        href={link.url!}
                                        className={[
                                            'px-2.5 py-1.5 text-xs rounded transition-colors',
                                            isActive
                                                ? 'bg-indigo-600 text-white font-semibold'
                                                : 'text-gray-600 hover:bg-gray-200',
                                        ].join(' ')}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            })
                            : Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => {
                                const isActive = page === pagination.current_page;
                                const href = baseUrl
                                    ? `${baseUrl}?page=${page}`
                                    : `?page=${page}`;
                                return isActive ? (
                                    <span
                                        key={page}
                                        className="px-2.5 py-1.5 text-xs rounded bg-indigo-600 text-white font-semibold"
                                    >
                                        {page}
                                    </span>
                                ) : (
                                    <Link
                                        key={page}
                                        href={href}
                                        className="px-2.5 py-1.5 text-xs rounded text-gray-600 hover:bg-gray-200 transition-colors"
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
