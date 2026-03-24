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
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-kintsugi-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18C2.504 7.5 2 8.004 2 8.625v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
        ),
        title: 'Embalagem Artesanal',
        description: 'Cada peca embalada com cuidado e materiais sustentaveis.',
    },
    {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-kintsugi-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.075 6.075 0 006.075 6.075h2.1a6.075 6.075 0 006.075-6.075V4.575" />
            </svg>
        ),
        title: 'Direto do Artesao',
        description: 'Compre diretamente de quem cria. Sem intermediarios.',
    },
    {
        icon: (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7 text-kintsugi-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
            </svg>
        ),
        title: 'Pecas Unicas',
        description: 'Edicoes limitadas e trabalhos exclusivos feitos a mao.',
    },
];

const CATEGORY_ICONS: Record<string, string> = {
    ceramicas: '🏺',
    texteis: '🧵',
    arte: '🎨',
    papelaria: '✂️',
    joias: '💎',
    velas: '🕯️',
    decoracao: '🛋️',
    jardim: '🌿',
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
                            <p className="text-xs font-semibold uppercase tracking-widest text-kintsugi-500 mb-1">Browse</p>
                            <h2 id="categories-heading" className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700">Categorias</h2>
                        </div>
                        <Link href="/products" className="text-sm font-semibold text-kintsugi-500 hover:text-kintsugi-600 transition-colors">
                            Ver todos →
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                        {categories.map((cat) => (
                            <Link
                                key={cat.id}
                                href={`/products?category_id=${cat.id}`}
                                className="group flex flex-col items-center gap-3 rounded-2xl border border-warm-200 bg-warm-50 p-5 text-center hover:border-kintsugi-200 hover:bg-kintsugi-50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <span className="text-3xl" role="img" aria-hidden="true">
                                    {CATEGORY_ICONS[cat.slug] ?? '🛍️'}
                                </span>
                                <span className="text-sm font-semibold text-warm-600 group-hover:text-kintsugi-600 transition-colors">
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
                            <p className="text-xs font-semibold uppercase tracking-widest text-kintsugi-500 mb-1">Selecionados</p>
                            <h2 id="featured-heading" className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700">Pecas em Destaque</h2>
                        </div>
                        <Link href="/products" className="text-sm font-semibold text-kintsugi-500 hover:text-kintsugi-600 transition-colors">
                            Ver todos →
                        </Link>
                    </div>
                    <ProductGrid products={featured_products.slice(0, 8)} />
                </div>
            </section>

            {/* Why us */}
            <section className="bg-gradient-to-br from-kintsugi-50 to-kintsugi-50 py-14" aria-labelledby="features-heading">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <p className="text-xs font-semibold uppercase tracking-widest text-kintsugi-500 mb-1">Diferenciais</p>
                        <h2 id="features-heading" className="font-display text-2xl sm:text-3xl font-extrabold text-warm-700">Por que escolher Shopsugi&#x30C4;?</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        {FEATURES.map((feature) => (
                            <div
                                key={feature.title}
                                className="flex flex-col items-center text-center rounded-2xl bg-white border border-kintsugi-100 p-8 shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-kintsugi-50 mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="text-base font-bold text-warm-700 mb-2">{feature.title}</h3>
                                <p className="text-sm text-warm-500 leading-relaxed">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA bottom */}
            <section className="bg-warm-800 py-16">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="font-display text-2xl sm:text-3xl font-extrabold text-white mb-4">Junte-se a comunidade</h2>
                    <p className="text-warm-400 mb-8 leading-relaxed">
                        Cadastre-se e receba acesso antecipado a novas colecoes e edicoes limitadas.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/register"
                            className="rounded-full bg-kintsugi-500 px-8 py-3 text-sm font-bold text-white hover:bg-kintsugi-400 transition-colors shadow-lg"
                        >
                            Criar conta grátis
                        </Link>
                        <Link
                            href="/products"
                            className="rounded-full border border-warm-600 px-8 py-3 text-sm font-semibold text-warm-400 hover:border-warm-400 hover:text-white transition-colors"
                        >
                            Ver produtos
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
