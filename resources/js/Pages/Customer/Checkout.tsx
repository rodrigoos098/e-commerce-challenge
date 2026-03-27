import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { Resolver } from 'react-hook-form';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import PublicLayout from '@/Layouts/PublicLayout';
import Spinner from '@/Components/Shared/Spinner';
import type { CheckoutPageProps, Cart, SavedAddress } from '@/types/public';
import { formatPrice } from '@/utils/format';
import { appRoutes } from '@/utils/routes';
import {
  getProductImageSrc,
  handleProductImageError,
  ProductImageFallback,
} from '@/utils/productImage';

const checkoutSchema = z
  .object({
    shipping_mode: z.enum(['saved', 'new']),
    shipping_address_id: z.number().optional(),
    shipping_name: z.string(),
    shipping_street: z.string(),
    shipping_city: z.string(),
    shipping_state: z.string(),
    shipping_zip: z.string(),
    shipping_country: z.string(),
    same_billing: z.boolean(),
    billing_mode: z.enum(['saved', 'new']).optional(),
    billing_address_id: z.number().optional(),
    billing_name: z.string(),
    billing_street: z.string(),
    billing_city: z.string(),
    billing_state: z.string(),
    billing_zip: z.string(),
    billing_country: z.string(),
    notes: z.string().optional(),
    payment_simulated: z.boolean(),
  })
  .superRefine((data, ctx) => {
    if (data.shipping_mode === 'saved' && !data.shipping_address_id) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Selecione um endereço salvo para entrega.',
        path: ['shipping_address_id'],
      });
    }

    if (data.shipping_mode === 'new') {
      const shippingFields: Array<[keyof typeof data, string]> = [
        ['shipping_name', 'Nome do destinatário'],
        ['shipping_street', 'Endereço'],
        ['shipping_city', 'Cidade'],
        ['shipping_state', 'Estado'],
        ['shipping_zip', 'CEP'],
        ['shipping_country', 'País'],
      ];

      shippingFields.forEach(([field, label]) => {
        if (!String(data[field] ?? '').trim()) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} obrigatório`,
            path: [field],
          });
        }
      });
    }

    if (!data.same_billing) {
      if (data.billing_mode === 'saved' && !data.billing_address_id) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: 'Selecione um endereço salvo para cobrança.',
          path: ['billing_address_id'],
        });
      }

      if (data.billing_mode === 'new') {
        const billingFields: Array<[keyof typeof data, string]> = [
          ['billing_name', 'Nome do destinatário'],
          ['billing_street', 'Endereço'],
          ['billing_city', 'Cidade'],
          ['billing_state', 'Estado'],
          ['billing_zip', 'CEP'],
          ['billing_country', 'País'],
        ];

        billingFields.forEach(([field, label]) => {
          if (!String(data[field] ?? '').trim()) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} obrigatório`,
              path: [field],
            });
          }
        });
      }
    }
  });

type CheckoutFormData = z.infer<typeof checkoutSchema>;

function formatSavedAddress(address: SavedAddress): string {
  return `${address.street}, ${address.city} - ${address.state}, ${address.zip_code}`;
}

interface AddressFieldsProps {
  prefix: 'shipping' | 'billing';
  register: ReturnType<typeof useForm<CheckoutFormData>>['register'];
  errors: ReturnType<typeof useForm<CheckoutFormData>>['formState']['errors'];
}

function AddressFields({ prefix, register, errors }: AddressFieldsProps) {
  const field = (
    key: keyof CheckoutFormData,
    label: string,
    placeholder: string,
    colSpan = 'col-span-2'
  ) => (
    <div className={colSpan}>
      <label htmlFor={String(key)} className="mb-1.5 block text-sm font-semibold text-warm-600">
        {label}
      </label>
      <input
        id={String(key)}
        {...register(key)}
        placeholder={placeholder}
        className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-warm-400 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500 ${errors[key]?.message ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
      />
      {errors[key]?.message && (
        <p role="alert" className="mt-1 text-xs text-red-600">
          {errors[key]?.message}
        </p>
      )}
    </div>
  );

  return (
    <div className="grid grid-cols-2 gap-4">
      {field(`${prefix}_name` as keyof CheckoutFormData, 'Nome do destinatário', 'João da Silva')}
      {field(`${prefix}_street` as keyof CheckoutFormData, 'Endereço', 'Rua Exemplo, 123')}
      {field(`${prefix}_city` as keyof CheckoutFormData, 'Cidade', 'São Paulo', 'col-span-1')}
      {field(`${prefix}_state` as keyof CheckoutFormData, 'Estado', 'SP', 'col-span-1')}
      {field(`${prefix}_zip` as keyof CheckoutFormData, 'CEP', '01310-100', 'col-span-1')}
      {field(`${prefix}_country` as keyof CheckoutFormData, 'País', 'Brasil', 'col-span-1')}
    </div>
  );
}

interface SavedAddressSelectorProps {
  addresses: SavedAddress[];
  kind: 'shipping' | 'billing';
  selectedId?: number;
  error?: string;
  onSelect: (id: number) => void;
}

function SavedAddressSelector({
  addresses,
  kind,
  selectedId,
  error,
  onSelect,
}: SavedAddressSelectorProps) {
  return (
    <div>
      <div className="space-y-3">
        {addresses.map((address) => (
          <label
            key={address.id}
            className={`block cursor-pointer rounded-2xl border p-4 transition-all ${selectedId === address.id ? 'border-kintsugi-500 bg-kintsugi-50 shadow-sm' : 'border-warm-200 bg-white hover:border-warm-300'}`}
          >
            <div className="flex items-start gap-3">
              <input
                type="radio"
                name={`${kind}_address_id`}
                checked={selectedId === address.id}
                onChange={() => onSelect(address.id)}
                className="mt-1 h-4 w-4 border-warm-300 text-kintsugi-500 focus:ring-kintsugi-500"
              />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <p className="text-sm font-bold text-warm-700">{address.label}</p>
                  {address.is_default_shipping && (
                    <span className="rounded-full bg-kintsugi-100 px-2 py-0.5 text-[11px] font-semibold text-kintsugi-700">
                      Entrega padrão
                    </span>
                  )}
                  {address.is_default_billing && (
                    <span className="rounded-full bg-warm-200 px-2 py-0.5 text-[11px] font-semibold text-warm-700">
                      Cobrança padrão
                    </span>
                  )}
                </div>
                <p className="mt-1 text-sm font-medium text-warm-600">{address.recipient_name}</p>
                <p className="mt-1 text-sm text-warm-500">{formatSavedAddress(address)}</p>
                <p className="text-sm text-warm-500">{address.country}</p>
              </div>
            </div>
          </label>
        ))}
      </div>
      {error && (
        <p role="alert" className="mt-2 text-xs text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}

interface AddressChoiceProps {
  title: string;
  description: string;
  mode: 'saved' | 'new';
  onModeChange: (mode: 'saved' | 'new') => void;
  savedAddresses: SavedAddress[];
  children: React.ReactNode;
}

function AddressChoice({
  title,
  description,
  mode,
  onModeChange,
  savedAddresses,
  children,
}: AddressChoiceProps) {
  return (
    <div>
      <div className="mb-5">
        <h2 className="text-base font-bold text-warm-700">{title}</h2>
        <p className="mt-1 text-sm text-warm-500">{description}</p>
      </div>

      {savedAddresses.length > 0 && (
        <div className="mb-5 grid gap-3 sm:grid-cols-2">
          <button
            type="button"
            onClick={() => onModeChange('saved')}
            aria-pressed={mode === 'saved'}
            className={`rounded-2xl border px-4 py-3 text-left transition-all ${mode === 'saved' ? 'border-kintsugi-500 bg-kintsugi-50 text-kintsugi-700' : 'border-warm-200 bg-white text-warm-600 hover:border-warm-300'}`}
          >
            <span className="block text-sm font-bold">Usar endereço salvo</span>
            <span className="mt-1 block text-xs">Selecione um cadastro existente.</span>
          </button>
          <button
            type="button"
            onClick={() => onModeChange('new')}
            aria-pressed={mode === 'new'}
            className={`rounded-2xl border px-4 py-3 text-left transition-all ${mode === 'new' ? 'border-kintsugi-500 bg-kintsugi-50 text-kintsugi-700' : 'border-warm-200 bg-white text-warm-600 hover:border-warm-300'}`}
          >
            <span className="block text-sm font-bold">Preencher novo endereço</span>
            <span className="mt-1 block text-xs">Use outro endereço sem salvar agora.</span>
          </button>
        </div>
      )}

      {mode === 'new' && children}

      {savedAddresses.length === 0 && (
        <div className="mt-4 rounded-2xl border border-dashed border-warm-300 bg-warm-50 p-4 text-sm text-warm-600">
          Nenhum endereço salvo ainda.{' '}
          <Link
            href={appRoutes.customer.addresses.index}
            className="font-semibold text-kintsugi-600 underline underline-offset-4"
          >
            Cadastre um endereço
          </Link>{' '}
          ou preencha manualmente.
        </div>
      )}
    </div>
  );
}

interface OrderSummaryProps {
  cart: Cart;
}

function OrderSummary({ cart }: OrderSummaryProps) {
  return (
    <div className="sticky top-24 rounded-2xl border border-warm-200 bg-white p-6 shadow-sm">
      <h2 className="mb-5 text-base font-bold text-warm-700">Resumo do pedido</h2>
      <div className="mb-5 space-y-3">
        {cart.items.map((item) => (
          <div key={item.id} className="flex items-center gap-3">
            <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-warm-50">
              <img
                src={getProductImageSrc(item.product)}
                alt={item.product.name}
                className="h-full w-full object-cover"
                loading="lazy"
                onError={handleProductImageError}
              />
              <ProductImageFallback />
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-xs font-medium text-warm-700">{item.product.name}</p>
              <p className="text-xs text-warm-400">Qtd: {item.quantity}</p>
            </div>
            <p className="shrink-0 text-xs font-semibold text-warm-700">
              {formatPrice(item.product.price * item.quantity)}
            </p>
          </div>
        ))}
      </div>
      <div className="space-y-2 border-t border-warm-200 pt-4 text-sm">
        <div className="flex justify-between text-warm-600">
          <span>Subtotal</span>
          <span>{formatPrice(cart.subtotal)}</span>
        </div>
        <div className="flex justify-between text-warm-600">
          <span>Impostos</span>
          <span>{formatPrice(cart.tax)}</span>
        </div>
        <div className="flex justify-between text-warm-600">
          <span>Frete</span>
          <span className={cart.shipping_is_free ? 'font-medium text-green-600' : ''}>
            {cart.shipping_is_free ? 'Grátis' : formatPrice(cart.shipping_cost)}
          </span>
        </div>
        <div className="rounded-xl bg-warm-50 px-3 py-2 text-xs text-warm-500">
          <p className="font-semibold text-warm-600">{cart.shipping_rule_label}</p>
          <p className="mt-1">{cart.shipping_rule_description}</p>
        </div>
        <div className="flex justify-between border-t border-warm-200 pt-2 text-base font-bold text-warm-700">
          <span>Total</span>
          <span>{formatPrice(cart.total)}</span>
        </div>
      </div>
    </div>
  );
}

const STEPS = ['Entrega', 'Cobrança', 'Revisão', 'Pagamento'];

function StepBar({ current }: { current: number }) {
  return (
    <nav aria-label="Etapas do checkout" className="mb-8">
      <ol className="flex items-center gap-0">
        {STEPS.map((label, idx) => {
          const stepNum = idx + 1;
          const done = current > stepNum;
          const active = current === stepNum;

          return (
            <React.Fragment key={label}>
              <li className="flex flex-col items-center gap-1.5">
                <div
                  className={`flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-bold transition-all ${done ? 'border-kintsugi-600 bg-kintsugi-600 text-white' : active ? 'border-kintsugi-600 bg-white text-kintsugi-600' : 'border-warm-200 bg-white text-warm-400'}`}
                >
                  {done ? '✓' : stepNum}
                </div>
                <span
                  className={`hidden text-xs font-medium sm:block ${active ? 'text-kintsugi-600' : done ? 'text-warm-500' : 'text-warm-400'}`}
                >
                  {label}
                </span>
              </li>
              {idx < STEPS.length - 1 && (
                <div
                  className={`mx-2 mb-5 h-0.5 flex-1 transition-colors ${done ? 'bg-kintsugi-600' : 'bg-warm-200'}`}
                  aria-hidden="true"
                />
              )}
            </React.Fragment>
          );
        })}
      </ol>
    </nav>
  );
}

export default function Checkout({ cart, addresses }: CheckoutPageProps) {
  const [step, setStep] = useState<1 | 2 | 3 | 4>(1);
  const [submitting, setSubmitting] = useState(false);
  const hasBootstrappedShippingTotals = useRef(false);

  const defaultShippingAddress = useMemo(
    () => addresses.find((address) => address.is_default_shipping) ?? addresses[0],
    [addresses]
  );
  const defaultBillingAddress = useMemo(
    () => addresses.find((address) => address.is_default_billing) ?? addresses[0],
    [addresses]
  );

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    trigger,
    formState: { errors },
  } = useForm<CheckoutFormData>({
    resolver: zodResolver(checkoutSchema) as Resolver<CheckoutFormData>,
    defaultValues: {
      shipping_mode: defaultShippingAddress ? 'saved' : 'new',
      shipping_address_id: defaultShippingAddress?.id,
      shipping_name: '',
      shipping_street: '',
      shipping_city: '',
      shipping_state: '',
      shipping_zip: '',
      shipping_country: 'Brasil',
      same_billing: true,
      billing_mode: defaultBillingAddress ? 'saved' : 'new',
      billing_address_id: defaultBillingAddress?.id,
      billing_name: '',
      billing_street: '',
      billing_city: '',
      billing_state: '',
      billing_zip: '',
      billing_country: 'Brasil',
      notes: '',
      payment_simulated: false,
    },
  });

  const shippingMode = watch('shipping_mode');
  const billingMode = watch('billing_mode') ?? 'saved';
  const shippingAddressId = watch('shipping_address_id');
  const billingAddressId = watch('billing_address_id');
  const sameBilling = watch('same_billing');
  const paymentSimulated = watch('payment_simulated');
  const shippingName = watch('shipping_name');
  const shippingStreet = watch('shipping_street');
  const shippingCity = watch('shipping_city');
  const shippingState = watch('shipping_state');
  const shippingZip = watch('shipping_zip');
  const shippingCountry = watch('shipping_country');

  useEffect(() => {
    if (!hasBootstrappedShippingTotals.current) {
      hasBootstrappedShippingTotals.current = true;

      return;
    }

    const timeoutId = window.setTimeout(() => {
      router.get(
        appRoutes.customer.checkout,
        {
          shipping_mode: shippingMode,
          shipping_address_id: shippingMode === 'saved' ? shippingAddressId : undefined,
          shipping_zip: shippingMode === 'new' ? shippingZip : undefined,
        },
        {
          only: ['cart'],
          preserveState: true,
          preserveScroll: true,
          replace: true,
        }
      );
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [shippingAddressId, shippingMode, shippingZip]);

  useEffect(() => {
    if (!sameBilling) {
      return;
    }

    setValue('billing_mode', shippingMode, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_address_id', shippingAddressId, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_name', shippingName, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_street', shippingStreet, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_city', shippingCity, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_state', shippingState, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_zip', shippingZip, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
    setValue('billing_country', shippingCountry, {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate: false,
    });
  }, [
    sameBilling,
    setValue,
    shippingMode,
    shippingAddressId,
    shippingName,
    shippingStreet,
    shippingCity,
    shippingState,
    shippingZip,
    shippingCountry,
  ]);

  const goNextFromShipping = async () => {
    const fields: Array<keyof CheckoutFormData> =
      shippingMode === 'saved'
        ? ['shipping_mode', 'shipping_address_id']
        : [
            'shipping_mode',
            'shipping_name',
            'shipping_street',
            'shipping_city',
            'shipping_state',
            'shipping_zip',
            'shipping_country',
          ];

    if (await trigger(fields)) {
      setValue('payment_simulated', false, { shouldDirty: true, shouldValidate: false });
      setStep(2);
    }
  };

  const goNextFromBilling = async () => {
    const fields: Array<keyof CheckoutFormData> = sameBilling
      ? ['same_billing']
      : billingMode === 'saved'
        ? ['same_billing', 'billing_mode', 'billing_address_id']
        : [
            'same_billing',
            'billing_mode',
            'billing_name',
            'billing_street',
            'billing_city',
            'billing_state',
            'billing_zip',
            'billing_country',
          ];

    if (await trigger(fields)) {
      setValue('payment_simulated', false, { shouldDirty: true, shouldValidate: false });
      setStep(3);
    }
  };

  const goNextFromReview = async () => {
    if (await trigger(['notes'])) {
      setStep(4);
    }
  };

  const onSubmit = (data: CheckoutFormData) => {
    setSubmitting(true);
    router.post(appRoutes.customer.orders.store, data, {
      onSuccess: () => toast.success('Pagamento simulado e pedido concluido com sucesso!'),
      onError: () => toast.error('Erro ao finalizar pedido. Verifique os dados.'),
      onFinish: () => setSubmitting(false),
    });
  };

  return (
    <PublicLayout title="Checkout">
      <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 className="mb-2 text-2xl font-extrabold text-warm-700 sm:text-3xl">Finalizar Compra</h1>
        <p className="mb-8 text-sm text-warm-500">
          Escolha um endereço salvo ou preencha um novo para concluir seu pedido.
        </p>

        <StepBar current={step} />

        <form onSubmit={handleSubmit(onSubmit)}>
          <input type="hidden" {...register('payment_simulated')} />
          <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div className="lg:col-span-2">
              {step === 1 && (
                <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm sm:p-8">
                  <AddressChoice
                    title="Endereço de Entrega"
                    description="Use um endereço salvo para acelerar a compra ou informe um novo local de entrega."
                    mode={shippingMode}
                    onModeChange={(mode) => {
                      setValue('shipping_mode', mode, { shouldDirty: true });

                      if (mode === 'saved' && defaultShippingAddress && !shippingAddressId) {
                        setValue('shipping_address_id', defaultShippingAddress.id, {
                          shouldDirty: true,
                        });
                      }
                    }}
                    savedAddresses={addresses}
                  >
                    <AddressFields prefix="shipping" register={register} errors={errors} />
                  </AddressChoice>

                  {shippingMode === 'saved' && addresses.length > 0 && (
                    <div className="mt-5">
                      <SavedAddressSelector
                        addresses={addresses}
                        kind="shipping"
                        selectedId={shippingAddressId}
                        error={errors.shipping_address_id?.message}
                        onSelect={(id) => setValue('shipping_address_id', id)}
                      />
                    </div>
                  )}

                  <div className="mt-6 flex justify-end">
                    <button
                      type="button"
                      onClick={goNextFromShipping}
                      className="rounded-xl bg-kintsugi-500 px-6 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600"
                    >
                      Próximo →
                    </button>
                  </div>
                </div>
              )}

              {step === 2 && (
                <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm sm:p-8">
                  <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <h2 className="text-base font-bold text-warm-700">Endereço de Cobrança</h2>
                      <p className="mt-1 text-sm text-warm-500">
                        Defina um endereço próprio de cobrança ou reutilize o endereço de entrega.
                      </p>
                    </div>
                    <label className="flex items-center gap-2 text-sm font-medium text-warm-600">
                      <input
                        type="checkbox"
                        checked={sameBilling}
                        onChange={(event) => setValue('same_billing', event.target.checked)}
                        className="h-4 w-4 rounded border-warm-300 text-kintsugi-500 focus:ring-kintsugi-500"
                      />
                      Mesmo endereço da entrega
                    </label>
                  </div>

                  {sameBilling ? (
                    <div className="rounded-2xl border border-kintsugi-200 bg-kintsugi-50 p-4 text-sm text-kintsugi-700">
                      O pedido usará o mesmo snapshot do endereço de entrega para cobrança.
                    </div>
                  ) : (
                    <>
                      <AddressChoice
                        title="Dados de cobrança"
                        description="Escolha um cadastro salvo ou informe um endereço diferente para cobrança."
                        mode={billingMode}
                        onModeChange={(mode) => setValue('billing_mode', mode)}
                        savedAddresses={addresses}
                      >
                        <AddressFields prefix="billing" register={register} errors={errors} />
                      </AddressChoice>

                      {billingMode === 'saved' && addresses.length > 0 && (
                        <div className="mt-5">
                          <SavedAddressSelector
                            addresses={addresses}
                            kind="billing"
                            selectedId={billingAddressId}
                            error={errors.billing_address_id?.message}
                            onSelect={(id) => setValue('billing_address_id', id)}
                          />
                        </div>
                      )}
                    </>
                  )}

                  <div className="mt-6 flex justify-between">
                    <button
                      type="button"
                      onClick={() => setStep(1)}
                      className="rounded-xl border border-warm-200 px-6 py-2.5 text-sm font-semibold text-warm-600 transition-all hover:bg-warm-50"
                    >
                      ← Anterior
                    </button>
                    <button
                      type="button"
                      onClick={goNextFromBilling}
                      className="rounded-xl bg-kintsugi-500 px-6 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600"
                    >
                      Próximo →
                    </button>
                  </div>
                </div>
              )}

              {step === 3 && (
                <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm sm:p-8">
                  <h2 className="mb-6 text-base font-bold text-warm-700">
                    Observações e Confirmação
                  </h2>

                  <label
                    htmlFor="notes"
                    className="mb-1.5 block text-sm font-semibold text-warm-600"
                  >
                    Observações (opcional)
                  </label>
                  <textarea
                    id="notes"
                    {...register('notes')}
                    rows={4}
                    placeholder="Instruções especiais para entrega, etc."
                    className="w-full rounded-xl border border-warm-200 bg-warm-50 px-4 py-2.5 text-sm placeholder-warm-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500"
                  />

                  <div className="mt-6 rounded-2xl border border-dashed border-warm-300 bg-warm-50 p-4 text-sm text-warm-600">
                    O pedido salvará uma cópia dos endereços escolhidos, mantendo o histórico mesmo
                    se você editar seus cadastros depois.
                  </div>

                  <div className="mt-6 flex justify-between">
                    <button
                      type="button"
                      onClick={() => setStep(2)}
                      className="rounded-xl border border-warm-200 px-6 py-2.5 text-sm font-semibold text-warm-600 transition-all hover:bg-warm-50"
                    >
                      ← Anterior
                    </button>
                    <button
                      type="button"
                      onClick={goNextFromReview}
                      className="rounded-xl bg-kintsugi-500 px-8 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600"
                    >
                      Ir para pagamento mock →
                    </button>
                  </div>
                </div>
              )}

              {step === 4 && (
                <div className="rounded-2xl border border-warm-200 bg-white p-6 shadow-sm sm:p-8">
                  <div className="rounded-3xl border border-kintsugi-200 bg-gradient-to-br from-kintsugi-50 via-white to-warm-50 p-6">
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <p className="text-xs font-bold uppercase tracking-[0.25em] text-kintsugi-600">
                          Pagamento mock
                        </p>
                        <h2 className="mt-2 text-xl font-bold text-warm-700">
                          Simule o pagamento antes de concluir
                        </h2>
                        <p className="mt-2 max-w-xl text-sm text-warm-600">
                          Esta loja usa uma aprovacao ficticia para o desafio. Ao clicar no botao
                          abaixo, registraremos o pedido como pago com o metodo de teste.
                        </p>
                      </div>
                      <div className="rounded-full border border-warm-200 bg-white px-3 py-1 text-xs font-semibold text-warm-500">
                        Metodo: cartao mock
                      </div>
                    </div>

                    <div className="mt-6 rounded-2xl border border-dashed border-warm-300 bg-white/80 p-4 text-sm text-warm-600">
                      <p className="font-semibold text-warm-700">Valor a registrar</p>
                      <p className="mt-1 text-lg font-bold text-kintsugi-700">
                        {formatPrice(cart.total)}
                      </p>
                    </div>

                    <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                      <button
                        type="button"
                        onClick={() => {
                          setValue('payment_simulated', true, {
                            shouldDirty: true,
                            shouldValidate: true,
                          });
                          toast.success(
                            'Pagamento mock aprovado. Agora voce pode concluir o pedido.'
                          );
                        }}
                        disabled={paymentSimulated}
                        className="rounded-xl bg-kintsugi-500 px-6 py-3 text-sm font-bold text-white transition-all hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:opacity-60"
                      >
                        {paymentSimulated ? 'Pagamento mock aprovado' : 'Simular pagamento'}
                      </button>
                      <p className="text-sm text-warm-500">
                        {paymentSimulated
                          ? 'Pagamento registrado. O pedido pode ser finalizado.'
                          : 'A finalizacao so e liberada depois desta simulacao.'}
                      </p>
                    </div>
                  </div>

                  <div className="mt-6 flex justify-between">
                    <button
                      type="button"
                      onClick={() => setStep(3)}
                      className="rounded-xl border border-warm-200 px-6 py-2.5 text-sm font-semibold text-warm-600 transition-all hover:bg-warm-50"
                    >
                      ← Anterior
                    </button>
                    <button
                      type="submit"
                      disabled={submitting || !paymentSimulated}
                      className="flex items-center gap-2 rounded-xl bg-kintsugi-500 px-8 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      {submitting && <Spinner />}
                      Concluir pedido
                    </button>
                  </div>
                </div>
              )}
            </div>

            <div className="hidden lg:block lg:col-span-1">
              <OrderSummary cart={cart} />
            </div>

            {(step === 3 || step === 4) && (
              <div className="lg:hidden">
                <OrderSummary cart={cart} />
              </div>
            )}
          </div>
        </form>
      </div>
    </PublicLayout>
  );
}
