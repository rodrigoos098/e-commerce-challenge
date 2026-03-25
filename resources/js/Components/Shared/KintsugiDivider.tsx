import React from 'react';
import { useScrollReveal } from '@/hooks/useScrollReveal';

interface KintsugiDividerProps {
    className?: string;
    variant?: 'default' | 'short' | 'corner';
}

/**
 * An irregular golden crack line inspired by Kintsugi (金継ぎ).
 * Draws itself with a stroke animation when scrolled into view.
 */
export default function KintsugiDivider({ className = '', variant = 'default' }: KintsugiDividerProps) {
    const { ref, visible } = useScrollReveal<SVGSVGElement>(0.3);

    if (variant === 'short') {
        return (
            <svg
                ref={ref}
                viewBox="0 0 400 24"
                preserveAspectRatio="none"
                className={`kintsugi-divider-short ${visible ? 'is-visible' : ''} ${className}`}
                aria-hidden="true"
                style={{ height: '24px', width: '200px' }}
            >
                <path d="M0,12 C40,12 60,4 100,9 C140,14 160,6 200,10 C240,14 280,6 320,10 C360,14 380,12 400,12" />
            </svg>
        );
    }

    if (variant === 'corner') {
        return (
            <svg
                ref={ref}
                viewBox="0 0 100 100"
                className={`kintsugi-accent ${visible ? 'is-visible' : ''} ${className}`}
                aria-hidden="true"
            >
                <path d="M0,0 C20,10 30,40 50,45 C70,50 90,80 100,100" />
                <path d="M50,45 C60,30 80,20 90,10" style={{ strokeWidth: 1, opacity: 0.5 }} />
            </svg>
        );
    }

    return (
        <svg
            ref={ref}
            viewBox="0 0 1200 48"
            preserveAspectRatio="none"
            className={`kintsugi-divider ${visible ? 'is-visible' : ''} ${className}`}
            aria-hidden="true"
        >
            {/* Main irregular golden crack */}
            <path d="M0,24 C80,24 120,8 200,18 C280,28 320,12 400,20 C450,24 500,38 560,24 C620,10 680,30 740,22 C800,14 860,32 920,20 C980,8 1040,28 1100,18 C1140,12 1170,24 1200,24" />
            {/* Secondary thin crack branching off */}
            <path d="M400,20 C420,6 460,14 480,8" style={{ strokeWidth: 1, opacity: 0.5 }} />
            <path d="M740,22 C760,36 790,30 810,38" style={{ strokeWidth: 1, opacity: 0.5 }} />
        </svg>
    );
}
