import { useEffect, useRef, useState } from 'react';

/**
 * Observes an element and adds `is-visible` when it enters the viewport.
 * Returns a ref to attach to the target element.
 */
export function useScrollReveal<T extends Element = HTMLDivElement>(
    threshold = 0.15,
) {
    const ref = useRef<T>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = ref.current;
        if (!el) { return; }

        // Respect reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setVisible(true);
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { threshold },
        );
        observer.observe(el);
        return () => observer.disconnect();
    }, [threshold]);

    return { ref, visible };
}
