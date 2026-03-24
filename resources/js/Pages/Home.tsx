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

const CATEGORY_ICONS: Record<string, React.ReactNode> = {
    ceramicas: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M12 3C8 3 5 6 5 10c0 2.5 1 4.5 2.5 6H5l1 3h12l1-3h-2.5C18 14.5 19 12.5 19 10c0-4-3-7-7-7z" /></svg>
    ),
    texteis: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
    ),
    arte: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" /></svg>
    ),
    papelaria: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
    ),
    joias: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18C2.504 7.5 2 8.004 2 8.625v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
    ),
    velas: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" /><path strokeLinecap="round" strokeLinejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" /></svg>
    ),
    decoracao: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
    ),
    jardim: (
        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
    ),
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
                            <h2 id="categories-heading" className="font-display text-2xl sm:text-3xl font-bold text-warm-700">Categorias</h2>
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
                                className="group flex flex-col items-center gap-3 rounded-2xl border border-warm-200 bg-warm-50 p-6 text-center hover:border-kintsugi-200 hover:bg-kintsugi-50 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <span className="text-kintsugi-500 group-hover:text-kintsugi-600 transition-colors">
                                    {CATEGORY_ICONS[cat.slug] ?? (
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                                    )}
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
                            <h2 id="featured-heading" className="font-display text-2xl sm:text-3xl font-bold text-warm-700">Pecas em Destaque</h2>
                        </div>
                        <Link href="/products" className="text-sm font-semibold text-kintsugi-500 hover:text-kintsugi-600 transition-colors">
                            Ver todos →
                        </Link>
                    </div>
                    <ProductGrid products={featured_products.slice(0, 8)} />
                </div>
            </section>

            {/* Why us */}
            <section className="bg-kintsugi-50 py-14" aria-labelledby="features-heading">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <p className="text-xs font-semibold uppercase tracking-widest text-kintsugi-500 mb-1">Diferenciais</p>
                        <h2 id="features-heading" className="font-display text-2xl sm:text-3xl font-bold text-warm-700">Por que escolher Shopsugi&#x30C4;?</h2>
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
                                <h3 className="font-display text-lg font-bold text-warm-700 mb-2">{feature.title}</h3>
                                <p className="text-sm text-warm-500 leading-relaxed">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA bottom */}
            <section className="bg-warm-800 py-16">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 className="font-display text-2xl sm:text-3xl font-bold text-white mb-4">Junte-se a comunidade</h2>
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
