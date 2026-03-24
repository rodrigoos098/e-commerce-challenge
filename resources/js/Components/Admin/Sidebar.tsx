import React from 'react';
import { Link } from '@inertiajs/react';

export interface SidebarItem {
    label: string;
    href: string;
    icon?: React.ReactNode;
    badge?: string | number;
}

interface SidebarProps {
    items: SidebarItem[];
    activeItem?: string;
}

export default function Sidebar({ items, activeItem }: SidebarProps) {
    return (
        <nav className="space-y-0.5">
            {items.map((item) => {
                const isActive = activeItem
                    ? item.href === activeItem || item.href.startsWith(activeItem + '/')
                    : false;

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group',
                            isActive
                                ? 'bg-kintsugi-600 text-white shadow-sm'
                                : 'text-warm-400 hover:text-white hover:bg-warm-800',
                        ].join(' ')}
                    >
                        {item.icon && (
                            <span
                                className={[
                                    'flex-shrink-0 transition-colors',
                                    isActive
                                        ? 'text-white'
                                        : 'text-warm-400 group-hover:text-white',
                                ].join(' ')}
                            >
                                {item.icon}
                            </span>
                        )}
                        <span className="flex-1 truncate">{item.label}</span>
                        {item.badge !== undefined && (
                            <span className="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0">
                                {item.badge}
                            </span>
                        )}
                    </Link>
                );
            })}
        </nav>
    );
}
