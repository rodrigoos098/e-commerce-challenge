import React from 'react';
import { Link } from '@inertiajs/react';

interface HeroBannerProps {
    title?: string;
    subtitle?: string;
    ctaLabel?: string;
    ctaHref?: string;
}

export default function HeroBanner({
    title = 'Beleza nas imperfeições.\nArte em cada detalhe.',
    subtitle = 'Descubra peças únicas feitas à mão por artesãos independentes. Cerâmicas, têxteis, joias e muito mais.',
    ctaLabel = 'Explorar Coleção',
    ctaHref = '/products',
}: HeroBannerProps) {
    return (
        <section className="relative overflow-hidden bg-gradient-to-br from-warm-800 via-warm-900 to-warm-900 py-20 sm:py-28 lg:py-36">
            {/* Background decorative circles */}
            <div className="pointer-events-none absolute inset-0" aria-hidden="true">
                <div className="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-kintsugi-500/10 blur-3xl" />
                <div className="absolute bottom-0 left-1/4 h-72 w-72 rounded-full bg-kintsugi-400/10 blur-2xl" />
                <div className="absolute top-1/3 right-1/3 h-48 w-48 rounded-full bg-kintsugi-300/15 blur-2xl" />
            </div>

            <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {/* Badge */}
                <span className="mb-6 inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-sm px-4 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                    <span className="h-1.5 w-1.5 rounded-full bg-kintsugi-400 animate-pulse" />
                    Novas peças artesanais toda semana
                </span>

                <h1 className="mt-4 whitespace-pre-line font-display text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl leading-tight">
                    {title}
                </h1>

                <p className="mt-6 text-lg text-white/75 sm:text-xl max-w-2xl mx-auto leading-relaxed">
                    {subtitle}
                </p>

                <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href={ctaHref}
                        className="inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-sm font-bold text-kintsugi-700 shadow-xl hover:bg-kintsugi-50 transition-all duration-200 hover:scale-105 active:scale-100"
                    >
                        {ctaLabel}
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                    <Link
                        href="/register"
                        className="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 backdrop-blur-sm px-8 py-3.5 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-200"
                    >
                        Criar conta grátis
                    </Link>
                </div>

                {/* Stats */}
                <div className="mt-14 grid grid-cols-3 gap-6 border-t border-white/10 pt-10">
                    {[
                        { value: '500+', label: 'Artesãos' },
                        { value: '2k+', label: 'Peças únicas' },
                        { value: '4.9★', label: 'Avaliação' },
                    ].map((stat) => (
                        <div key={stat.label}>
                            <div className="text-2xl sm:text-3xl font-extrabold text-white">{stat.value}</div>
                            <div className="text-xs sm:text-sm text-white/60 mt-1">{stat.label}</div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
