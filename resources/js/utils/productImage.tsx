import React from 'react';

interface ProductImageLike {
  id: number;
  image_url?: string | null;
}

export function getProductImageSrc(product: ProductImageLike): string {
  return product.image_url ?? `/storage/products/${product.id}.webp`;
}

export function handleProductImageError(event: React.SyntheticEvent<HTMLImageElement>): void {
  const image = event.currentTarget;
  image.style.display = 'none';

  const fallback = image.parentElement?.querySelector<HTMLElement>('[data-product-image-fallback]');

  if (fallback) {
    fallback.style.display = 'flex';
  }
}

export function ProductImageFallback({ label = 'Imagem indisponivel' }: { label?: string }) {
  return (
    <div
      data-product-image-fallback
      className="hidden h-full w-full flex-col items-center justify-center gap-1.5 bg-warm-50 px-2 text-center"
      style={{ display: 'none' }}
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        className="h-6 w-6 shrink-0 text-warm-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={1.5}
        aria-hidden="true"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21zM8.25 8.25h.008v.008H8.25V8.25z"
        />
      </svg>
      <span className="hidden text-[10px] font-semibold uppercase tracking-[0.2em] text-warm-400 sm:block">
        {label}
      </span>
    </div>
  );
}
