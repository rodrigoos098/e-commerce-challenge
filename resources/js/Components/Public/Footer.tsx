import React from 'react';
import { Link } from '@inertiajs/react';
import KintsugiDivider from '@/Components/Shared/KintsugiDivider';

export default function Footer() {
    return (
        <footer className="bg-warm-800 text-warm-300">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    {/* Brand — elevated Kintsugi tagline */}
                    <div className="sm:col-span-1">
                        <Link href="/" className="inline-block mb-4">
                            <span className="font-display text-2xl font-bold text-kintsugi-400">
                                Shopsugi<span className="text-kintsugi-300">ツ</span>
                            </span>
                        </Link>
                        <p className="font-display text-base italic text-warm-300 leading-relaxed mb-3">
                            Arte feita à mão, entregue com carinho.
                        </p>
                        <p className="text-xs text-warm-500">
                            Inspirado pelo <span className="text-kintsugi-400/80 font-medium">Kintsugi</span> — a arte de reparar com ouro, celebrando a beleza nas imperfeições.
                        </p>
                    </div>

                    {/* Navegação */}
                    <div>
                        <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Navegação</h3>
                        <ul className="space-y-2">
                            {[
                                { label: 'Início', href: '/' },
                                { label: 'Coleção', href: '/products' },
                                { label: 'Meu Carrinho', href: '/cart' },
                                { label: 'Meus Pedidos', href: '/customer/orders' },
                            ].map((link) => (
                                <li key={link.href}>
                                    <Link href={link.href} className="text-sm text-warm-400 hover:text-kintsugi-400 transition-colors">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Conta */}
                    <div>
                        <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Minha Conta</h3>
                        <ul className="space-y-2">
                            {[
                                { label: 'Fazer Login', href: '/login' },
                                { label: 'Criar Conta', href: '/register' },
                                { label: 'Meu Perfil', href: '/customer/profile' },
                            ].map((link) => (
                                <li key={link.href}>
                                    <Link href={link.href} className="text-sm text-warm-400 hover:text-kintsugi-400 transition-colors">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="mt-10 border-t border-warm-700 pt-6 text-center">
                    <div className="flex justify-center mb-4">
                        <KintsugiDivider variant="short" className="opacity-30" />
                    </div>
                    <p className="text-xs text-warm-500">
                        © {new Date().getFullYear()} Shopsugiツ. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>
    );
}
