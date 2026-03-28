const priceFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

const dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
  day: '2-digit',
  month: 'long',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
});

/**
 * Format a numeric value as Brazilian Real currency.
 *
 * @example formatPrice(199.9) // "R$ 199,90"
 */
export function formatPrice(value: number): string {
  return priceFormatter.format(value);
}

export function formatCurrencyInput(value: number | null | undefined): string {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return '';
  }

  return formatPrice(value);
}

export function parseCurrencyInput(value: string): number | null {
  const digits = value.replace(/\D/g, '');

  if (!digits) {
    return null;
  }

  return Number(digits) / 100;
}

export function formatDateTime(value: string | Date): string {
  return dateTimeFormatter.format(new Date(value));
}

export function formatDate(value: string | Date): string {
  return dateFormatter.format(new Date(value));
}
