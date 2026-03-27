import React, { useRef, useEffect, useMemo, useState } from 'react';
import type { Product } from '@/types/shared';
import {
  getProductImageSrc,
  handleProductImageError,
  ProductImageFallback,
} from '@/utils/productImage';

// ——— Predefined layout (asymmetric, intentional composition) ——
const LAYOUT = [
  { x: 25, y: 8, size: 120, depth: 0.9, rotation: -5 },
  { x: 65, y: 20, size: 90, depth: 0.55, rotation: 4 },
  { x: 5, y: 35, size: 85, depth: 0.4, rotation: -3 },
  { x: 40, y: 45, size: 105, depth: 0.8, rotation: 3 },
  { x: 12, y: 70, size: 90, depth: 0.65, rotation: -4 },
  { x: 55, y: 75, size: 70, depth: 0.3, rotation: 5 },
];

const CONNECTIONS: [number, number][] = [
  [0, 1],
  [0, 3],
  [2, 3],
  [3, 5],
  [4, 5],
];

interface Props {
  products: Product[];
}

export default function KintsugiConstellation({ products }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [size, setSize] = useState({ w: 0, h: 0 });

  // Observe container size for SVG viewBox
  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    const ro = new ResizeObserver((entries) => {
      const { width, height } = entries[0].contentRect;
      setSize({ w: width, h: height });
    });
    ro.observe(el);
    return () => ro.disconnect();
  }, []);

  // Map products to predefined layout positions
  const photos = useMemo(
    () =>
      products.slice(0, LAYOUT.length).map((p, i) => ({
        id: p.id,
        src: getProductImageSrc(p),
        alt: p.name,
        ...LAYOUT[i],
      })),
    [products]
  );

  // Filter connections to valid photo indices
  const validConnections = useMemo(
    () => CONNECTIONS.filter(([a, b]) => a < photos.length && b < photos.length),
    [photos.length]
  );

  // Pre-compute SVG paths at base positions
  const lines = useMemo(() => {
    if (!size.w || !size.h) return [];
    return validConnections.map(([a, b]) => {
      const pa = LAYOUT[a],
        pb = LAYOUT[b];
      const ax = (pa.x / 100) * size.w + pa.size / 2;
      const ay = (pa.y / 100) * size.h + pa.size / 2;
      const bx = (pb.x / 100) * size.w + pb.size / 2;
      const by = (pb.y / 100) * size.h + pb.size / 2;
      // Perpendicular offset at midpoint for organic bezier curve
      const mx = (ax + bx) / 2,
        my = (ay + by) / 2;
      const dx = bx - ax,
        dy = by - ay;
      return `M${ax},${ay} Q${mx - dy * 0.15},${my + dx * 0.15} ${bx},${by}`;
    });
  }, [size, validConnections]);

  if (!photos.length) return null;

  return (
    <div ref={containerRef} className="relative h-full w-full" aria-hidden="true">
      {/* SVG kintsugi connection lines */}
      {size.w > 0 && (
        <svg
          className="absolute inset-0 h-full w-full"
          viewBox={`0 0 ${size.w} ${size.h}`}
          fill="none"
        >
          <defs>
            <filter id="kintsugi-line-glow">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feMerge>
                <feMergeNode in="blur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>
          {lines.map((d, i) => (
            <path
              key={i}
              d={d}
              stroke="var(--color-kintsugi-400)"
              strokeWidth="1.5"
              strokeLinecap="round"
              opacity="0.3"
              filter="url(#kintsugi-line-glow)"
              style={{
                strokeDasharray: 600,
                strokeDashoffset: 600,
                animation: `kintsugi-draw 1.8s cubic-bezier(0.22, 1, 0.36, 1) ${0.6 + i * 0.2}s forwards`,
              }}
            />
          ))}
        </svg>
      )}

      {/* Floating product photos */}
      {photos.map((photo, i) => (
        <div
          key={photo.id}
          className="absolute"
          style={{
            left: `${photo.x}%`,
            top: `${photo.y}%`,
            width: photo.size,
            height: photo.size,
          }}
        >
          {/* CSS float animation wrapper */}
          <div
            className="h-full w-full"
            style={
              {
                '--float-y': `${-6 - photo.depth * 4}px`,
                animation: `constellation-float ${3 + photo.depth * 2}s ease-in-out ${i * 0.4}s infinite`,
              } as React.CSSProperties
            }
          >
            {/* Static rotation */}
            <div className="h-full w-full" style={{ transform: `rotate(${photo.rotation}deg)` }}>
              {/* Photo card */}
              <div
                className="relative h-full w-full overflow-hidden rounded-2xl border border-warm-200/50 bg-white shadow-lg shadow-warm-900/5 animate-fade-up"
                style={{ animationDelay: `${300 + i * 150}ms` }}
              >
                <img
                  src={photo.src}
                  alt=""
                  className="h-full w-full object-cover"
                  loading="lazy"
                  draggable={false}
                  onError={handleProductImageError}
                />
                <ProductImageFallback />
              </div>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
