import React from 'react';
import { Link } from '@inertiajs/react';

export default function Footer() {
    return (
        <footer className="bg-warm-800 text-warm-300">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    {/* Brand */}
                    <div className="lg:col-span-1">
                        <Link href="/" className="inline-block mb-4">
                            <span className="font-display text-2xl font-bold text-kintsugi-400">
                                Shopsugi<span className="text-kintsugi-300">ツ</span>
                            </span>
                        </Link>
                        <p className="text-sm text-warm-400 leading-relaxed">
                            Arte feita à mão, entregue com carinho. Cada peça conta uma história.
                        </p>
                    </div>

                    {/* Navegação */}
                    <div>
                        <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Navegação</h3>
                        <ul className="space-y-2">
                            {[
                                { label: 'Início', href: '/' },
                                { label: 'Produtos', href: '/products' },
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

                    {/* Contato */}
                    <div>
                        <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contato</h3>
                        <ul className="space-y-2 text-sm text-warm-400">
                            <li>📧 contato@shopsugi.com</li>
                            <li>📞 (11) 99999-9999</li>
                            <li>🕘 Seg–Sex, 9h–18h</li>
                        </ul>
                    </div>
                </div>

                <div className="mt-10 border-t border-warm-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p className="text-xs text-warm-500">
                        © {new Date().getFullYear()} Shopsugiツ. Todos os direitos reservados.
                    </p>
                    <p className="text-xs text-warm-500">
                        Feito com amor, inspirado pelo Kintsugi
                    </p>
                </div>
            </div>
        </footer>
    );
}
