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

            <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="max-w-3xl">
                    {/* Badge */}
                    <span className="mb-6 block text-sm font-semibold tracking-widest text-kintsugi-600 uppercase animate-fade-up">
                        Feito à Mão no Brasil
                    </span>

                    {/* Title with asymmetrical scale and line-height */}
                    <h1 className="mt-2 font-display text-4xl font-extrabold tracking-tight text-warm-900 sm:text-5xl lg:text-7xl animate-fade-up" style={{ lineHeight: 1.1, animationDelay: '100ms' }}>
                        {titleLines[0]}
                        {titleLines[1] && (
                            <>
                                <br />
                                <span className="kintsugi-shimmer">{titleLines[1]}</span>
                            </>
                        )}
                    </h1>

                    <p className="mt-8 text-xl text-warm-600 leading-relaxed animate-fade-up max-w-xl" style={{ animationDelay: '250ms' }}>
                        {subtitle}
                    </p>

                    {/* CTA Group with gap-based spacing */}
                    <div className="mt-12 flex flex-col sm:flex-row items-stretch sm:items-center gap-4 animate-fade-up" style={{ animationDelay: '400ms' }}>
                        <Link
                            href={ctaHref}
                            className="group inline-flex items-center justify-center gap-2 rounded-full bg-kintsugi-500 px-8 py-4 text-sm font-bold text-white shadow-md shadow-kintsugi-500/20 hover:bg-kintsugi-400 hover:shadow-kintsugi-400/30 transition duration-200 active:scale-[.98]"
                        >
                            {ctaLabel}
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center gap-2 rounded-full border border-warm-300 bg-white/50 backdrop-blur-sm px-8 py-4 text-sm font-semibold text-warm-700 hover:bg-warm-100 transition duration-200"
                        >
                            Criar conta grátis
                        </Link>
                    </div>

                    {/* Stats — integrated naturally into copy with deep separation */}
                    {stats && stats.product_count > 0 && (
                        <div className="mt-20 border-l-2 border-kintsugi-200 pl-6 animate-fade-up" style={{ animationDelay: '500ms' }}>
                            <p className="text-sm font-medium text-warm-500 sm:text-base max-w-md">
                                Explore uma curadoria de <span className="font-bold text-warm-700">{stats.product_count} peças exclusivas</span>{' '}
                                distribuídas em <span className="font-bold text-warm-700">{stats.category_count} modalidades artesanais</span>.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
