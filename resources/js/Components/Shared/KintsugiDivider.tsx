import React from 'react';
import { useScrollReveal } from '@/hooks/useScrollReveal';

/**
 * An irregular golden crack line inspired by Kintsugi (金継ぎ).
 * Draws itself with a stroke animation when scrolled into view.
 */
export default function KintsugiDivider({ className = '' }: { className?: string }) {
    const { ref, visible } = useScrollReveal<SVGSVGElement>(0.3);

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
