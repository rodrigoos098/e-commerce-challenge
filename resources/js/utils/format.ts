/**
 * Format a numeric value as Brazilian Real currency.
 *
 * @example formatPrice(199.9) // "R$ 199,90"
 */
export function formatPrice(value: number): string {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
