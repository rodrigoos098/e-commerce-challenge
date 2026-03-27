import { useCallback } from 'react';

/**
 * Hook to play a subtle "ceramic clink" sound programmatically.
 * Uses Web Audio API to avoid external asset dependencies.
 */
export function useCartSound() {
    const playCartSound = useCallback(() => {
        // Respect user preference for reduced motion/distractions
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        // Check if sounds are muted in localStorage
        if (localStorage.getItem('shopsugi_sound_muted') === 'true') {
            return;
        }

        try {
            const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext;
            if (!AudioContextClass) return;

            const ctx = new AudioContextClass();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            // Frequency: ~600Hz (warm ceramic tink)
            osc.type = 'sine';
            osc.frequency.setValueAtTime(600, ctx.currentTime);
            // Quick pitch slide up to make it "lighter"
            osc.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.05);

            // Envelope: Fast attack, quick decay
            gain.gain.setValueAtTime(0, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.15, ctx.currentTime + 0.01); // Soft volume
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3); // Fade out

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);

            // Close context after playback
            setTimeout(() => {
                if (ctx.state !== 'closed') {
                    ctx.close();
                }
            }, 500);
        } catch (error) {
            // Silently fail if audio context cannot be started
            console.warn('Audio playback failed:', error);
        }
    }, []);

    return { playCartSound };
}
