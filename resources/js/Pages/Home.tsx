import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import HeroBanner from '@/Components/Public/HeroBanner';
import ProductGrid from '@/Components/Public/ProductGrid';
import type { HomePageProps } from '@/types/public';

// ——— Feature cards ————————————————————————————————————————

const FEATURES = [
    {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
        ),
        title: 'Frete Rápido',
        description: 'Entrega em até 3 dias úteis para todo o Brasil.',
    },
    {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        ),
        title: 'Compra Segura',
        description: 'Seus dados protegidos com criptografia SSL.',
    },
    {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        ),
        title: 'Suporte 24h',
        description: 'Atendimento disponível a qualquer hora do dia.',
    },
];

const CATEGORY_ICONS: Record<string, string> = {
    eletronicos: '💻',
    roupas: '👕',
    esportes: '⚽',
    'casa-jardim': '🏠',
    livros: '📚',
    beleza: '💄',
};

export default function Home({ featured_products, categories }: HomePageProps) {
    return (
        <PublicLayout title="Início">
            {/* Hero */}
            <HeroBanner />

            {/* Categories */}
            <section className="bg-white py-14" aria-labelledby="categories-heading">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-end justify-between mb-8">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-widest text-violet-600 mb-1">Browse</p>
                            <h2 id="categories-heading" className="text-2xl sm:text-3xl font-extrabold text-gray-900">Categorias</h2>
                        </div>
                        <Link href="/products" className="text-sm font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                            Ver todos →
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                        {categories.map((cat) => (
                            <Link
                                key={cat.id}
                                href={`/products?category_id=${cat.id}`}
                                className="group flex flex-col items-center gap-3 rounded-2xl border border-gray-100 bg-gray-50 p-5 text-center hover:border-violet-200 hover:bg-violet-50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <span className="text-3xl" role="img" aria-hidden="true">
                                    {CATEGORY_ICONS[cat.slug] ?? '🛍️'}
                                </span>
                                <span className="text-sm font-semibold text-gray-700 group-hover:text-violet-700 transition-colors">
                                    {cat.name}
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Featured products */}
            <section className="py-14" aria-labelledby="featured-heading">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-end justify-between mb-8">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-widest text-violet-600 mb-1">Selecionados</p>
                            <h2 id="featured-heading" className="text-2xl sm:text-3xl font-extrabold text-gray-900">Produtos em Destaque</h2>
                        </div>
                        <Link href="/products" className="text-sm font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                            Ver todos →
                        </Link>
                    </div>
                    <ProductGrid products={featured_products.slice(0, 8)} />
                </div>
            </section>

            {/* Why us */}
            <section className="bg-gradient-to-br from-violet-50 to-indigo-50 py-14" aria-labelledby="features-heading">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <p className="text-xs font-semibold uppercase tracking-widest text-violet-600 mb-1">Diferenciais</p>
                        <h2 id="features-heading" className="text-2xl sm:text-3xl font-extrabold text-gray-900">Por que comprar conosco?</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        {FEATURES.map((feature) => (
                            <div
                                key={feature.title}
                                className="flex flex-col items-center text-center rounded-2xl bg-white border border-violet-100 p-8 shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="text-base font-bold text-gray-900 mb-2">{feature.title}</h3>
                                <p className="text-sm text-gray-500 leading-relaxed">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA bottom */}
            <section className="bg-gray-900 py-16">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="text-2xl sm:text-3xl font-extrabold text-white mb-4">Pronto para começar?</h2>
                    <p className="text-gray-400 mb-8 leading-relaxed">
                        Crie sua conta agora e aproveite ofertas exclusivas para novos clientes.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/register"
                            className="rounded-full bg-violet-600 px-8 py-3 text-sm font-bold text-white hover:bg-violet-500 transition-colors shadow-lg"
                        >
                            Criar conta grátis
                        </Link>
                        <Link
                            href="/products"
                            className="rounded-full border border-gray-600 px-8 py-3 text-sm font-semibold text-gray-300 hover:border-gray-400 hover:text-white transition-colors"
                        >
                            Ver produtos
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
