import React from 'react';
import { Link } from '@inertiajs/react';
import KintsugiDivider from '@/Components/Shared/KintsugiDivider';

interface HeroBannerProps {
    title?: string;
    subtitle?: string;
    ctaLabel?: string;
    ctaHref?: string;
    stats?: {
        product_count: number;
        category_count: number;
    };
}

export default function HeroBanner({
    title = 'Beleza nas imperfeições.\nArte em cada detalhe.',
    subtitle = 'Descubra peças únicas feitas à mão por artesãos independentes. Cerâmicas, têxteis, joias e muito mais.',
    ctaLabel = 'Explorar Coleção',
    ctaHref = '/products',
    stats,
}: HeroBannerProps) {
    // Split title to apply shimmer to the second line
    const titleLines = title.split('\n');

    return (
        <section className="relative overflow-hidden bg-gradient-to-br from-cream via-parchment to-warm-100 py-24 sm:py-32 lg:py-40">
            {/* Background: organic kintsugi-inspired decorative cracks */}
            <div className="pointer-events-none absolute inset-0" aria-hidden="true">
                <div className="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-kintsugi-400/10 blur-3xl" />
                <div className="absolute bottom-0 left-1/4 h-72 w-72 rounded-full bg-kintsugi-300/10 blur-3xl" />
                {/* Diagonal decorative gold line — subtle organic feel */}
                <svg className="absolute top-1/3 left-0 w-full h-24 opacity-20" viewBox="0 0 1200 80" preserveAspectRatio="none">
                    <path d="M0,40 C150,10 300,70 450,35 C600,0 750,60 900,30 C1050,0 1150,50 1200,40" stroke="currentColor" strokeWidth="1.5" fill="none" className="text-kintsugi-400" />
                </svg>

                {/* Kintsugi Corner Accents */}
                <KintsugiDivider variant="corner" className="top-right" />
                <KintsugiDivider variant="corner" className="bottom-left" />
            </div>

            <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {/* Badge */}
                <span className="mb-4 block text-sm font-semibold tracking-widest text-kintsugi-600 uppercase animate-fade-up">
                    Feito à Mão no Brasil
                </span>

                {/* Title with shimmer on accent line */}
                <h1 className="mt-4 font-display text-4xl font-extrabold tracking-tight text-warm-800 sm:text-5xl lg:text-7xl animate-fade-up" style={{ lineHeight: 1.05, animationDelay: '100ms' }}>
                    {titleLines[0]}
                    {titleLines[1] && (
                        <>
                            <br />
                            <span className="kintsugi-shimmer">{titleLines[1]}</span>
                        </>
                    )}
                </h1>

                <p className="mt-8 text-lg text-warm-500 sm:text-xl max-w-2xl mx-auto leading-relaxed animate-fade-up" style={{ animationDelay: '250ms' }}>
                    {subtitle}
                </p>

                <div className="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-up" style={{ animationDelay: '400ms' }}>
                    <Link
                        href={ctaHref}
                        className="group inline-flex items-center gap-2 rounded-full bg-kintsugi-500 px-9 py-4 text-sm font-bold text-white shadow-lg shadow-kintsugi-500/25 hover:bg-kintsugi-400 hover:shadow-kintsugi-400/30 transition-all duration-200 active:scale-[.97]"
                    >
                        {ctaLabel}
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                    <Link
                        href="/register"
                        className="inline-flex items-center gap-2 rounded-full border border-warm-300 bg-warm-100 px-9 py-4 text-sm font-semibold text-warm-700 hover:bg-warm-200 transition-all duration-200"
                    >
                        Criar conta grátis
                    </Link>
                </div>

                {/* Stats — only rendered when real data is available */}
                {stats && (
                    <div className="mt-16 inline-flex items-center gap-8 sm:gap-12 border-t border-warm-200 pt-10 animate-fade-up" style={{ animationDelay: '500ms' }}>
                        <div className="text-center">
                            <div className="font-display text-3xl sm:text-4xl font-bold text-kintsugi-600">{stats.product_count}</div>
                            <div className="text-xs sm:text-sm text-warm-400 mt-1">Peças no catálogo</div>
                        </div>
                        <div className="h-10 w-px bg-warm-200" aria-hidden="true" />
                        <div className="text-center">
                            <div className="font-display text-3xl sm:text-4xl font-bold text-kintsugi-600">{stats.category_count}</div>
                            <div className="text-xs sm:text-sm text-warm-400 mt-1">Categorias</div>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
