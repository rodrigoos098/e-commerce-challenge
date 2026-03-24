import React, { useState, useEffect, useCallback } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Toaster, toast } from 'react-hot-toast';
import Footer from '@/Components/Public/Footer';

interface PublicLayoutProps {
    children: React.ReactNode;
    title?: string;
    cartCount?: number;
}

interface PageProps {
    auth?: { user?: { id: number; name: string; email: string; roles?: string[] } };
    flash?: { success?: string; error?: string };
    cart_count?: number;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

// ——— Icons ————————————————————————————————————————————————

const IconShoppingCart = ({ count }: { count: number }) => (
    <div className="relative">
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        {count > 0 && (
            <span className="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-kintsugi-500 text-xs font-bold text-white">
                {count > 99 ? '99+' : count}
            </span>
        )}
    </div>
);

const IconSearch = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-warm-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
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

const IconChevronDown = () => (
    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
);

// ——— Header ———————————————————————————————————————————————

function Header({ cartCount }: { cartCount: number }) {
    const { auth, flash } = usePage<PageProps>().props;
    const user = auth?.user;

    const [mobileOpen, setMobileOpen] = useState(false);
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [scrolled, setScrolled] = useState(false);

    // Flash messages
    useEffect(() => {
        if (flash?.success) { toast.success(flash.success); }
        if (flash?.error) { toast.error(flash.error); }
    }, [flash]);

    // Scroll shadow
    useEffect(() => {
        const handleScroll = () => setScrolled(window.scrollY > 10);
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchQuery.trim()) {
                router.get('/products', { search: searchQuery }, { preserveScroll: true, preserveState: true });
            }
        }, 400);
        return () => clearTimeout(timer);
    }, [searchQuery]);

    const handleLogout = useCallback(() => {
        router.post('/logout');
    }, []);

    const navLinks = [
        { label: 'Início', href: '/' },
        { label: 'Coleção', href: '/products' },
        { label: 'Categorias', href: '/products?category=all' },
    ];

    return (
        <header
            className={`sticky top-0 z-50 bg-white border-b border-warm-200 transition-shadow duration-200 ${scrolled ? 'shadow-md' : ''}`}
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-4">
                    {/* Logo */}
                    <Link href="/" className="flex items-center gap-2 shrink-0">
                        <span className="font-display text-2xl font-bold text-kintsugi-600 tracking-tight">
                            Shopsugi<span className="text-kintsugi-400">ツ</span>
                        </span>
                    </Link>

                    {/* Desktop nav */}
                    <nav className="hidden md:flex items-center gap-6">
                        {navLinks.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="text-sm font-medium text-warm-600 hover:text-kintsugi-500 transition-colors duration-150"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>

                    {/* Search */}
                    <div className="hidden sm:flex flex-1 max-w-xs lg:max-w-sm">
                        <div className="relative w-full">
                            <span className="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                <IconSearch />
                            </span>
                            <input
                                type="search"
                                placeholder="Buscar produtos..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full rounded-full border border-warm-200 bg-warm-50 py-2 pl-10 pr-4 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all"
                            />
                        </div>
                    </div>

                    {/* Right actions */}
                    <div className="flex items-center gap-3 shrink-0">
                        {/* Cart */}
                        <Link
                            href="/cart"
                            className="relative text-warm-600 hover:text-kintsugi-500 transition-colors duration-150 p-1"
                            aria-label={`Carrinho com ${cartCount} itens`}
                        >
                            <IconShoppingCart count={cartCount} />
                        </Link>

                        {/* User */}
                        {user ? (
                            <div className="relative">
                                <button
                                    onClick={() => setUserDropdownOpen((v) => !v)}
                                    className="flex items-center gap-1.5 rounded-full bg-gray-100 pl-2 pr-3 py-1.5 text-sm font-medium text-warm-600 hover:bg-gray-200 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-kintsugi-500"
                                    aria-haspopup="true"
                                    aria-expanded={userDropdownOpen}
                                >
                                    <div className="flex h-6 w-6 items-center justify-center rounded-full bg-kintsugi-500 text-xs font-bold text-white">
                                        {user.name.charAt(0).toUpperCase()}
                                    </div>
                                    <span className="hidden sm:block max-w-[80px] truncate">{user.name.split(' ')[0]}</span>
                                    <IconChevronDown />
                                </button>

                                {userDropdownOpen && (
                                    <>
                                        <div
                                            className="fixed inset-0 z-10"
                                            onClick={() => setUserDropdownOpen(false)}
                                            aria-hidden="true"
                                        />
                                        <div className="absolute right-0 z-20 mt-2 w-52 rounded-xl bg-white shadow-xl border border-warm-200 py-1 overflow-hidden">
                                            <div className="px-4 py-2 border-b border-warm-200">
                                                <p className="text-xs text-warm-500">Conectado como</p>
                                                <p className="text-sm font-semibold text-warm-700 truncate">{user.email}</p>
                                            </div>
                                            <Link
                                                href="/customer/orders"
                                                className="block px-4 py-2 text-sm text-warm-600 hover:bg-warm-50 transition-colors"
                                                onClick={() => setUserDropdownOpen(false)}
                                            >
                                                Meus Pedidos
                                            </Link>
                                            <Link
                                                href="/customer/profile"
                                                className="block px-4 py-2 text-sm text-warm-600 hover:bg-warm-50 transition-colors"
                                                onClick={() => setUserDropdownOpen(false)}
                                            >
                                                Meu Perfil
                                            </Link>
                                            {user.roles?.includes('admin') && (
                                                <Link
                                                    href="/admin"
                                                    className="block px-4 py-2 text-sm text-kintsugi-500 font-medium hover:bg-kintsugi-50 transition-colors"
                                                    onClick={() => setUserDropdownOpen(false)}
                                                >
                                                    Painel Admin
                                                </Link>
                                            )}
                                            <div className="border-t border-warm-200 mt-1">
                                                <button
                                                    onClick={handleLogout}
                                                    className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                >
                                                    Sair
                                                </button>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        ) : (
                            <div className="hidden sm:flex items-center gap-2">
                                <Link
                                    href="/login"
                                    className="text-sm font-medium text-warm-600 hover:text-kintsugi-500 transition-colors"
                                >
                                    Entrar
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-full bg-kintsugi-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-kintsugi-600 transition-colors shadow-sm"
                                >
                                    Criar conta
                                </Link>
                            </div>
                        )}

                        {/* Mobile hamburger */}
                        <button
                            className="md:hidden text-warm-600 hover:text-kintsugi-500 transition-colors p-1"
                            onClick={() => setMobileOpen((v) => !v)}
                            aria-label="Abrir menu"
                        >
                            {mobileOpen ? <IconClose /> : <IconMenu />}
                        </button>
                    </div>
                </div>

                {/* Mobile search */}
                <div className="sm:hidden pb-3">
                    <div className="relative w-full">
                        <span className="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <IconSearch />
                        </span>
                        <input
                            type="search"
                            placeholder="Buscar produtos..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full rounded-full border border-warm-200 bg-warm-50 py-2 pl-10 pr-4 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent"
                        />
                    </div>
                </div>
            </div>

            {/* Mobile menu */}
            {mobileOpen && (
                <div className="md:hidden border-t border-warm-200 bg-white shadow-lg">
                    <nav className="flex flex-col px-4 py-3 gap-1">
                        {navLinks.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                onClick={() => setMobileOpen(false)}
                                className="block rounded-lg px-3 py-2 text-sm font-medium text-warm-600 hover:bg-warm-50 hover:text-kintsugi-500 transition-colors"
                            >
                                {link.label}
                            </Link>
                        ))}
                        {!user && (
                            <>
                                <div className="border-t border-warm-200 mt-2 pt-2 flex flex-col gap-1">
                                    <Link
                                        href="/login"
                                        onClick={() => setMobileOpen(false)}
                                        className="block rounded-lg px-3 py-2 text-sm font-medium text-warm-600 hover:bg-warm-50"
                                    >
                                        Entrar
                                    </Link>
                                    <Link
                                        href="/register"
                                        onClick={() => setMobileOpen(false)}
                                        className="block rounded-lg px-3 py-2 text-sm font-medium bg-kintsugi-500 text-white text-center hover:bg-kintsugi-600"
                                    >
                                        Criar conta
                                    </Link>
                                </div>
                            </>
                        )}
                        {user && (
                            <div className="border-t border-warm-200 mt-2 pt-2 flex flex-col gap-1">
                                <Link
                                    href="/customer/orders"
                                    onClick={() => setMobileOpen(false)}
                                    className="block rounded-lg px-3 py-2 text-sm font-medium text-warm-600 hover:bg-warm-50"
                                >
                                    Meus Pedidos
                                </Link>
                                <Link
                                    href="/customer/profile"
                                    onClick={() => setMobileOpen(false)}
                                    className="block rounded-lg px-3 py-2 text-sm font-medium text-warm-600 hover:bg-warm-50"
                                >
                                    Meu Perfil
                                </Link>
                                <button
                                    onClick={handleLogout}
                                    className="block w-full text-left rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                >
                                    Sair
                                </button>
                            </div>
                        )}
                    </nav>
                </div>
            )}
        </header>
    );
}

// ——— Layout ———————————————————————————————————————————————

export default function PublicLayout({ children, title, cartCount = 0 }: PublicLayoutProps) {
    const { cart_count } = usePage<PageProps>().props;
    const effectiveCartCount = cart_count ?? cartCount;

    useEffect(() => {
        if (title) {
            document.title = `${title} — Shopsugiツ`;
        } else {
            document.title = 'Shopsugiツ — Arte em cada detalhe';
        }
    }, [title]);

    return (
        <div className="min-h-screen flex flex-col bg-warm-50">
            <Toaster position="top-right" toastOptions={{ duration: 4000 }} />
            <Header cartCount={effectiveCartCount} />
            <main className="flex-1">
                {children}
            </main>
            <Footer />
        </div>
    );
}
