import { appRoutes } from '@/utils/routes';

export interface CepLookupResult {
  found: boolean;
  street: string;
  city: string;
  state: string;
}

export function normalizeZipCode(zipCode: string): string {
  return zipCode.replace(/\D/g, '');
}

export async function lookupZipCode(
  zipCode: string,
  signal?: AbortSignal
): Promise<CepLookupResult | null> {
  const normalizedZipCode = normalizeZipCode(zipCode);

  if (normalizedZipCode.length !== 8) {
    return null;
  }

  const response = await fetch(
    `${appRoutes.customer.addresses.lookup}?zip_code=${normalizedZipCode}`,
    {
      headers: {
        Accept: 'application/json',
      },
      signal,
    }
  );

  if (!response.ok) {
    return null;
  }

  const payload = (await response.json()) as Partial<CepLookupResult>;

  if (!payload.found) {
    return null;
  }

  return {
    found: true,
    street: payload.street ?? '',
    city: payload.city ?? '',
    state: payload.state ?? '',
  };
}
