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
                <div className="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-kintsugi-500/8 blur-3xl" />
                <div className="absolute bottom-0 left-1/4 h-72 w-72 rounded-full bg-kintsugi-400/8 blur-3xl" />
            </div>

            <div className="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {/* Badge */}
                <span className="mb-6 inline-flex items-center gap-2 rounded-full bg-kintsugi-500/20 backdrop-blur-sm px-4 py-1.5 text-xs font-medium text-kintsugi-100 ring-1 ring-kintsugi-400/30">
                    Novas peças artesanais toda semana
                </span>

                <h1 className="mt-4 whitespace-pre-line font-display text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl" style={{ lineHeight: 1.08 }}>
                    {title}
                </h1>

                <p className="mt-6 text-lg text-white/80 sm:text-xl max-w-2xl mx-auto leading-relaxed">
                    {subtitle}
                </p>

                <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href={ctaHref}
                        className="inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-kintsugi-700 shadow-md hover:bg-kintsugi-50 transition-colors duration-200"
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
                <div className="mt-14 grid grid-cols-3 gap-x-10 gap-y-6 border-t border-white/10 pt-10">
                    {[
                        { value: '500+', label: 'Artesãos' },
                        { value: '2k+', label: 'Peças únicas' },
                        { value: '4.9★', label: 'Avaliação' },
                    ].map((stat) => (
                        <div key={stat.label}>
                            <div className="font-display text-2xl sm:text-3xl font-bold text-white">{stat.value}</div>
                            <div className="text-xs sm:text-sm text-white/60 mt-1">{stat.label}</div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
