import React, { useState, useEffect } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Toaster, toast } from 'react-hot-toast';

interface AdminLayoutProps {
    children: React.ReactNode;
    title?: string;
}

interface NavItem {
    label: string;
    href: string;
    icon: React.ReactNode;
    matchPaths?: string[];
}

// — Icons (inline SVG) —————————————————————————————————————
const IconDashboard = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-2a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z" />
    </svg>
);

const IconProducts = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
    </svg>
);

const IconCategories = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
    </svg>
);

const IconOrders = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
    </svg>
);

const IconStock = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
);

const IconTags = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M7 7h.01M7 3h5.586a2 2 0 011.414.586l5.414 5.414a2 2 0 010 2.828l-6.586 6.586a2 2 0 01-2.828 0L3.586 11.999A2 2 0 013 10.585V5a2 2 0 012-2h2z" />
    </svg>
);

const IconMenu = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
);

const IconClose = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
);

const IconLogout = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
    </svg>
);

const IconChevronRight = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
    </svg>
);

// — Helpers ————————————————————————————————————————————————
function getUserInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function buildBreadcrumbs(path: string, pageTitle?: string): Array<{ label: string; href?: string }> {
    const segments = path.replace(/^\/admin\/?/, '').split('/').filter(Boolean);
    const crumbs: Array<{ label: string; href?: string }> = [
        { label: 'Admin', href: '/admin/dashboard' },
    ];

    const labelMap: Record<string, string> = {
        dashboard: 'Dashboard',
        products: 'Produtos',
        categories: 'Categorias',
        tags: 'Tags',
        orders: 'Pedidos',
        stock: 'Estoque',
        create: 'Novo',
        edit: 'Editar',
        low: 'Estoque Baixo',
    };

    let accPath = '/admin';

    segments.forEach((seg, idx) => {
        accPath += `/${seg}`;
        const isLast = idx === segments.length - 1;
        crumbs.push({
            label: pageTitle && isLast ? pageTitle : (labelMap[seg] ?? seg),
            href: isLast ? undefined : accPath,
        });
    });

    return crumbs;
}

// — Nav items ————————————————————————————————————————————————
const navItems: NavItem[] = [
    {
        label: 'Dashboard',
        href: '/admin/dashboard',
        icon: <IconDashboard />,
        matchPaths: ['/admin/dashboard'],
    },
    {
        label: 'Produtos',
        href: '/admin/products',
        icon: <IconProducts />,
        matchPaths: ['/admin/products'],
    },
    {
        label: 'Categorias',
        href: '/admin/categories',
        icon: <IconCategories />,
        matchPaths: ['/admin/categories'],
    },
    {
        label: 'Tags',
        href: '/admin/tags',
        icon: <IconTags />,
        matchPaths: ['/admin/tags'],
    },
    {
        label: 'Pedidos',
        href: '/admin/orders',
        icon: <IconOrders />,
        matchPaths: ['/admin/orders'],
    },
    {
        label: 'Estoque Baixo',
        href: '/admin/stock/low',
        icon: <IconStock />,
        matchPaths: ['/admin/stock'],
    },
];

// — Component ————————————————————————————————————————————————
export default function AdminLayout({ children, title }: AdminLayoutProps) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { props, url } = usePage<{
        auth?: { user?: { name?: string; email?: string } };
        flash?: { success?: string; error?: string };
    }>();

    const currentPath = url ?? (typeof window !== 'undefined' ? window.location.pathname : '');
    const userName = props.auth?.user?.name ?? 'Administrador';
    const userEmail = props.auth?.user?.email ?? 'admin@loja.com';
    const breadcrumbs = buildBreadcrumbs(currentPath, title);

    // Flash messages
    useEffect(() => {
        if (props.flash?.success) {
            toast.success(props.flash.success);
        }
        if (props.flash?.error) {
            toast.error(props.flash.error);
        }
    }, [props.flash]);

    // Close sidebar on route change
    useEffect(() => {
        setSidebarOpen(false);
    }, [currentPath]);

    function isActive(item: NavItem): boolean {
        return (item.matchPaths ?? [item.href]).some(
            (p) => currentPath === p || currentPath.startsWith(p + '/'),
        );
    }

    function handleLogout() {
        router.post('/logout');
    }

    // — Sidebar content ————————————————————————————————————
    const SidebarContent = () => (
        <div className="flex flex-col h-full">
            {/* Logo */}
            <div className="flex items-center gap-2 px-6 py-5 border-b border-warm-800/60">
                <span className="font-display text-xl font-bold text-white tracking-tight">
                    Shopsugi<span className="ml-0.5 text-kintsugi-400 kintsugi-shimmer">ツ</span>
                </span>
                <span className="text-warm-500 text-[10px] uppercase tracking-widest ml-auto font-bold">Admin</span>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <p className="px-3 mb-2 text-xs font-semibold text-warm-500 uppercase tracking-wider">Menu</p>
                {navItems.map((item) => {
                    const active = isActive(item);
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group',
                                active
                                    ? 'bg-kintsugi-600 text-white shadow-sm'
                                    : 'text-warm-400 hover:text-white hover:bg-warm-800',
                            ].join(' ')}
                        >
                            <span
                                className={[
                                    'flex-shrink-0 transition-colors',
                                    active ? 'text-white' : 'text-warm-400 group-hover:text-white',
                                ].join(' ')}
                            >
                                {item.icon}
                            </span>
                            {item.label}
                            {item.label === 'Estoque Baixo' && (
                                <span className="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                                    !
                                </span>
                            )}
                        </Link>
                    );
                })}
            </nav>

            {/* User */}
            <div className="px-3 py-4 border-t border-warm-800/60">
                <div className="flex items-center gap-3 px-3 py-2 rounded-lg">
                    <div className="w-8 h-8 rounded-full bg-kintsugi-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {getUserInitials(userName)}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-white text-sm font-medium truncate">{userName}</p>
                        <p className="text-warm-400 text-xs truncate">{userEmail}</p>
                    </div>
                    <button
                        onClick={handleLogout}
                        aria-label="Sair"
                        title="Sair"
                        className="text-warm-400 hover:text-red-400 transition-colors flex-shrink-0"
                    >
                        <IconLogout />
                    </button>
                </div>
            </div>
        </div>
    );

    return (
        <div className="flex h-screen bg-warm-50 overflow-hidden">
            <Toaster position="top-right" toastOptions={{ duration: 4000 }} />

            {/* Skip navigation */}
            <a
                href="#admin-main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-2 focus:left-2 focus:rounded-lg focus:bg-kintsugi-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg"
            >
                Pular para conteúdo
            </a>

            {/* Mobile overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-20 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar (desktop: fixed, mobile: off-canvas) */}
            <aside
                className={[
                    'fixed inset-y-0 left-0 z-30 w-64 bg-warm-900 flex flex-col transition-transform duration-300 ease-in-out',
                    'lg:translate-x-0 lg:static lg:inset-auto lg:flex-shrink-0',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                ].join(' ')}
            >
                {/* Mobile close button */}
                <button
                    onClick={() => setSidebarOpen(false)}
                    aria-label="Fechar menu lateral"
                    className="absolute top-4 right-4 text-warm-400 hover:text-white lg:hidden"
                >
                    <IconClose />
                </button>

                <SidebarContent />
            </aside>

            {/* Main content */}
            <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
                {/* Header */}
                <header className="flex items-center gap-4 px-4 sm:px-6 h-16 bg-white border-b border-warm-200 flex-shrink-0">
                    {/* Hamburger (mobile) */}
                    <button
                        onClick={() => setSidebarOpen(true)}
                        aria-label="Abrir menu"
                        className="text-warm-500 hover:text-warm-600 lg:hidden"
                    >
                        <IconMenu />
                    </button>

                    {/* Breadcrumbs */}
                    <nav className="flex items-center gap-1.5 text-sm min-w-0" aria-label="Breadcrumb">
                        {breadcrumbs.map((crumb, idx) => (
                            <React.Fragment key={idx}>
                                {idx > 0 && (
                                    <span className="text-warm-400 flex-shrink-0">
                                        <IconChevronRight />
                                    </span>
                                )}
                                {crumb.href ? (
                                    <Link
                                        href={crumb.href}
                                        className="text-warm-500 hover:text-kintsugi-600 transition-colors truncate"
                                    >
                                        {crumb.label}
                                    </Link>
                                ) : (
                                    <span className="text-warm-700 font-medium truncate">
                                        {crumb.label}
                                    </span>
                                )}
                            </React.Fragment>
                        ))}
                    </nav>

                    {/* Right side: user info (desktop) */}
                    <div className="ml-auto flex items-center gap-3">
                        <div className="hidden sm:block text-right">
                            <p className="text-sm font-medium text-warm-700 leading-tight">{userName}</p>
                            <p className="text-xs text-warm-500">{userEmail}</p>
                        </div>
                        <div className="w-9 h-9 rounded-full bg-kintsugi-600 flex items-center justify-center text-white text-sm font-bold">
                            {getUserInitials(userName)}
                        </div>
                        <button
                            onClick={handleLogout}
                            aria-label="Sair"
                            title="Sair"
                            className="hidden sm:flex items-center gap-1.5 text-sm text-warm-500 hover:text-red-500 transition-colors"
                        >
                            <IconLogout />
                            <span>Sair</span>
                        </button>
                    </div>
                </header>

                {/* Page content */}
                <main id="admin-main-content" className="flex-1 overflow-y-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}
