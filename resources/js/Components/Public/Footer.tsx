import React from 'react';
import { Link } from '@inertiajs/react';

export default function Footer() {
    return (
        <footer className="bg-gray-900 text-gray-300">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    {/* Brand */}
                    <div className="lg:col-span-1">
                        <Link href="/" className="flex items-center gap-2 mb-4">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500">
                                <span className="text-white font-extrabold text-lg leading-none">E</span>
                            </div>
                            <span className="text-xl font-bold text-white">
                                E<span className="text-violet-400">Shop</span>
                            </span>
                        </Link>
                        <p className="text-sm text-gray-400 leading-relaxed">
                            Sua loja online com os melhores produtos e preços. Compre com segurança e rapidez.
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
                                    <Link href={link.href} className="text-sm text-gray-400 hover:text-violet-400 transition-colors">
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
                                    <Link href={link.href} className="text-sm text-gray-400 hover:text-violet-400 transition-colors">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Contato */}
                    <div>
                        <h3 className="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contato</h3>
                        <ul className="space-y-2 text-sm text-gray-400">
                            <li>📧 contato@eshop.com</li>
                            <li>📞 (11) 99999-9999</li>
                            <li>🕘 Seg–Sex, 9h–18h</li>
                        </ul>
                    </div>
                </div>

                <div className="mt-10 border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p className="text-xs text-gray-500">
                        © {new Date().getFullYear()} EShop. Todos os direitos reservados.
                    </p>
                    <p className="text-xs text-gray-500">
                        Desenvolvido com ❤️ usando Laravel + React
                    </p>
                </div>
            </div>
        </footer>
    );
}
