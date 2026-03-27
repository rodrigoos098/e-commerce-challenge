import React, { useEffect, useId, useRef } from 'react';
import Button from '@/Components/Shared/Button';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  onConfirm?: () => void;
  confirmLabel?: string;
  cancelLabel?: string;
  confirmDestructive?: boolean;
  loading?: boolean;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

const sizeMap = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-2xl',
};

const FOCUSABLE =
  'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default function Modal({
  isOpen,
  onClose,
  title,
  children,
  onConfirm,
  confirmLabel = 'Confirmar',
  cancelLabel = 'Cancelar',
  confirmDestructive = false,
  loading = false,
  size = 'md',
}: ModalProps) {
  const modalTitleId = useId();
  const panelRef = useRef<HTMLDivElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', handler);

    return () => window.removeEventListener('keydown', handler);
  }, [isOpen, onClose]);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
      previousFocusRef.current = document.activeElement as HTMLElement;

      const timer = window.setTimeout(() => {
        const firstFocusable = panelRef.current?.querySelector<HTMLElement>(FOCUSABLE);

        firstFocusable?.focus();
      }, 10);

      return () => window.clearTimeout(timer);
    }

    document.body.style.overflow = '';
    previousFocusRef.current?.focus();
  }, [isOpen]);

  const handleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
    if (event.key !== 'Tab' || !panelRef.current) {
      return;
    }

    const focusable = Array.from(panelRef.current.querySelectorAll<HTMLElement>(FOCUSABLE));

    if (focusable.length === 0) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey) {
      if (document.activeElement === first) {
        last.focus();
        event.preventDefault();
      }

      return;
    }

    if (document.activeElement === last) {
      first.focus();
      event.preventDefault();
    }
  };

  if (!isOpen) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby={modalTitleId}
      onKeyDown={handleKeyDown}
    >
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />

      <div
        ref={panelRef}
        className={[
          'relative flex max-h-[90vh] w-full flex-col rounded-xl border border-warm-200 bg-white shadow-xl',
          sizeMap[size],
        ].join(' ')}
      >
        <div className="flex shrink-0 items-center justify-between border-b border-warm-200 px-6 py-4">
          <h2 id={modalTitleId} className="text-base font-semibold text-warm-700">
            {title}
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-md p-1 text-warm-400 transition-colors hover:bg-warm-100 hover:text-warm-600"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-6 py-4">{children}</div>

        {onConfirm && (
          <div className="flex shrink-0 items-center justify-end gap-3 rounded-b-xl border-t border-warm-200 bg-warm-50 px-6 py-4">
            <Button
              type="button"
              variant="secondary"
              size="sm"
              onClick={onClose}
              disabled={loading}
            >
              {cancelLabel}
            </Button>
            <Button
              type="button"
              variant={confirmDestructive ? 'danger' : 'primary'}
              size="sm"
              loading={loading}
              onClick={onConfirm}
            >
              {confirmLabel}
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}
