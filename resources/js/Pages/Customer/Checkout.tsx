import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import PublicLayout from '@/Layouts/PublicLayout';
import type { CheckoutPageProps, Cart } from '@/types/public';

// ——— Schema ————————————————————————————————————————————————

const addrField = (label: string) => z.string().min(1, `${label} obrigatório`);

const checkoutSchema = z.object({
    shipping_name: addrField('Nome do destinatário'),
    shipping_street: addrField('Endereço'),
    shipping_city: addrField('Cidade'),
    shipping_state: z.string().min(2, 'Estado obrigatório'),
    shipping_zip: z.string().min(8, 'CEP inválido'),
    shipping_country: addrField('País'),
    same_billing: z.boolean(),
    billing_name: z.string().optional(),
    billing_street: z.string().optional(),
    billing_city: z.string().optional(),
    billing_state: z.string().optional(),
    billing_zip: z.string().optional(),
    billing_country: z.string().optional(),
    notes: z.string().optional(),
}).superRefine((data, ctx) => {
    if (!data.same_billing) {
        const billingFields: Array<{ key: keyof typeof data; label: string }> = [
            { key: 'billing_name', label: 'Nome do destinatário (cobrança)' },
            { key: 'billing_street', label: 'Endereço de cobrança' },
            { key: 'billing_city', label: 'Cidade de cobrança' },
            { key: 'billing_state', label: 'Estado de cobrança' },
            { key: 'billing_zip', label: 'CEP de cobrança' },
            { key: 'billing_country', label: 'País de cobrança' },
        ];
        billingFields.forEach(({ key, label }) => {
            if (!data[key]) {
                ctx.addIssue({ code: z.ZodIssueCode.custom, message: `${label} obrigatório`, path: [key] });
            }
        });
    }
});

type CheckoutFormData = z.infer<typeof checkoutSchema>;

// ——— Helpers ——————————————————————————————————————————————

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// ——— Sub-components ———————————————————————————————————————

interface AddressFieldsProps {
    prefix: 'shipping' | 'billing';
    register: ReturnType<typeof useForm<CheckoutFormData>>['register'];
    errors: ReturnType<typeof useForm<CheckoutFormData>>['formState']['errors'];
}

function AddressFields({ prefix, register, errors }: AddressFieldsProps) {
    const f = (name: keyof CheckoutFormData) => name;
    const err = (name: keyof CheckoutFormData) => errors[name]?.message;

    const field = (
        key: keyof CheckoutFormData,
        label: string,
        placeholder: string,
        colSpan = 'col-span-2',
    ) => (
        <div className={colSpan}>
            <label htmlFor={String(key)} className="block text-sm font-semibold text-gray-700 mb-1.5">
                {label}
            </label>
            <input
                id={String(key)}
                {...register(f(key))}
                placeholder={placeholder}
                className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                    ${err(key) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
            />
            {err(key) && <p role="alert" className="mt-1 text-xs text-red-600">{err(key)}</p>}
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

interface OrderSummaryProps {
    cart: Cart;
    onSubmit?: () => void;
    submitting?: boolean;
    showButton?: boolean;
}

function OrderSummary({ cart, onSubmit, submitting = false, showButton = false }: OrderSummaryProps) {
    return (
        <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sticky top-24">
            <h2 className="text-base font-bold text-gray-900 mb-5">Resumo do pedido</h2>
            <div className="space-y-3 mb-5">
                {cart.items.map((item) => (
                    <div key={item.id} className="flex items-center gap-3">
                        <div className="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                            <img
                                src={`https://picsum.photos/seed/${item.product.id}/96/96`}
                                alt={item.product.name}
                                className="h-full w-full object-cover"
                            />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-xs font-medium text-gray-800 truncate">{item.product.name}</p>
                            <p className="text-xs text-gray-400">Qtd: {item.quantity}</p>
                        </div>
                        <p className="text-xs font-semibold text-gray-800 shrink-0">
                            {formatPrice(item.product.price * item.quantity)}
                        </p>
                    </div>
                ))}
            </div>
            <div className="border-t border-gray-100 pt-4 space-y-2 text-sm">
                <div className="flex justify-between text-gray-600">
                    <span>Subtotal</span><span>{formatPrice(cart.subtotal)}</span>
                </div>
                <div className="flex justify-between text-gray-600">
                    <span>Impostos</span><span>{formatPrice(cart.tax)}</span>
                </div>
                <div className="flex justify-between text-gray-600">
                    <span>Frete</span>
                    <span>{cart.shipping_cost === 0 ? 'Grátis' : formatPrice(cart.shipping_cost)}</span>
                </div>
                <div className="flex justify-between font-bold text-base text-gray-900 pt-2 border-t border-gray-100">
                    <span>Total</span><span>{formatPrice(cart.total)}</span>
                </div>
            </div>
            {showButton && (
                <button
                    type="submit"
                    onClick={onSubmit}
                    disabled={submitting}
                    className="mt-6 w-full rounded-2xl bg-violet-600 py-3.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-lg shadow-violet-200 disabled:opacity-60 flex items-center justify-center gap-2"
                >
                    {submitting && (
                        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                    )}
                    Confirmar Pedido
                </button>
            )}
        </div>
    );
}

// ——— Progress bar ——————————————————————————————————————————

const STEPS = ['Entrega', 'Cobrança', 'Revisão'];

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
                                    className={`flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold border-2 transition-all ${
                                        done
                                            ? 'bg-violet-600 border-violet-600 text-white'
                                            : active
                                            ? 'bg-white border-violet-600 text-violet-600'
                                            : 'bg-white border-gray-200 text-gray-400'
                                    }`}
                                >
                                    {done ? (
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3} aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    ) : stepNum}
                                </div>
                                <span className={`text-xs font-medium hidden sm:block ${active ? 'text-violet-600' : done ? 'text-gray-500' : 'text-gray-400'}`}>
                                    {label}
                                </span>
                            </li>
                            {idx < STEPS.length - 1 && (
                                <div className={`flex-1 h-0.5 mx-2 mb-5 transition-colors ${done ? 'bg-violet-600' : 'bg-gray-200'}`} aria-hidden="true" />
                            )}
                        </React.Fragment>
                    );
                })}
            </ol>
        </nav>
    );
}

// ——— Page ————————————————————————————————————————————————

export default function Checkout({ cart }: CheckoutPageProps) {
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        getValues,
        formState: { errors },
    } = useForm<CheckoutFormData>({
        resolver: zodResolver(checkoutSchema) as Resolver<CheckoutFormData>,
        defaultValues: {
            shipping_name: '',
            shipping_street: '',
            shipping_city: '',
            shipping_state: '',
            shipping_zip: '',
            shipping_country: 'Brasil',
            same_billing: true,
            billing_name: '',
            billing_street: '',
            billing_city: '',
            billing_state: '',
            billing_zip: '',
            billing_country: 'Brasil',
            notes: '',
        },
    });

    const sameBilling = watch('same_billing');

    const shippingFields: Array<keyof CheckoutFormData> = [
        'shipping_name', 'shipping_street', 'shipping_city',
        'shipping_state', 'shipping_zip', 'shipping_country',
    ];
    const billingFields: Array<keyof CheckoutFormData> = [
        'billing_name', 'billing_street', 'billing_city',
        'billing_state', 'billing_zip', 'billing_country',
    ];

    const handleSameBillingChange = (checked: boolean) => {
        setValue('same_billing', checked);
        if (checked) {
            const vals = getValues();
            shippingFields.forEach((sf, i) => {
                setValue(billingFields[i], vals[sf] as string);
            });
        }
    };

    const goNext = async () => {
        setStep((s) => (s < 3 ? ((s + 1) as 1 | 2 | 3) : s));
    };

    const goBack = () => {
        setStep((s) => (s > 1 ? ((s - 1) as 1 | 2 | 3) : s));
    };

    const onSubmit = (data: CheckoutFormData) => {
        setSubmitting(true);
        router.post('/customer/orders', data, {
            onSuccess: () => toast.success('Pedido realizado com sucesso!'),
            onError: () => {
                toast.error('Erro ao finalizar pedido. Verifique os dados.');
                setSubmitting(false);
            },
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <PublicLayout title="Checkout">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10">
                <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2">Finalizar Compra</h1>
                <p className="text-sm text-gray-500 mb-8">Complete as etapas abaixo para concluir seu pedido.</p>

                <StepBar current={step} />

                <form onSubmit={handleSubmit(onSubmit)}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Steps */}
                        <div className="lg:col-span-2">

                            {/* Step 1 — Shipping */}
                            {step === 1 && (
                                <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                                    <h2 className="text-base font-bold text-gray-900 mb-6 flex items-center gap-2">
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">1</span>
                                        Endereço de Entrega
                                    </h2>
                                    <AddressFields prefix="shipping" register={register} errors={errors} />
                                    <div className="flex justify-end mt-6">
                                        <button
                                            type="button"
                                            onClick={goNext}
                                            className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200"
                                        >
                                            Próximo →
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Step 2 — Billing */}
                            {step === 2 && (
                                <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                                    <div className="flex items-center justify-between mb-6">
                                        <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">2</span>
                                            Endereço de Cobrança
                                        </h2>
                                        <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                            <input
                                                type="checkbox"
                                                checked={sameBilling}
                                                onChange={(e) => handleSameBillingChange(e.target.checked)}
                                                className="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />
                                            Mesmo que entrega
                                        </label>
                                    </div>

                                    {sameBilling ? (
                                        <div className="rounded-xl bg-violet-50 border border-violet-100 p-4 flex items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <p className="text-sm text-violet-700 font-medium">Usando o mesmo endereço de entrega para cobrança.</p>
                                        </div>
                                    ) : (
                                        <AddressFields prefix="billing" register={register} errors={errors} />
                                    )}

                                    <div className="flex justify-between mt-6">
                                        <button
                                            type="button"
                                            onClick={goBack}
                                            className="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-[.98] transition-all"
                                        >
                                            ← Anterior
                                        </button>
                                        <button
                                            type="button"
                                            onClick={goNext}
                                            className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200"
                                        >
                                            Próximo →
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Step 3 — Notes + submit */}
                            {step === 3 && (
                                <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                                    <h2 className="text-base font-bold text-gray-900 mb-6 flex items-center gap-2">
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">3</span>
                                        Observações e Confirmação
                                    </h2>

                                    <label htmlFor="notes" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                        Observações (opcional)
                                    </label>
                                    <textarea
                                        id="notes"
                                        {...register('notes')}
                                        placeholder="Instruções especiais para entrega, etc."
                                        rows={4}
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent resize-none"
                                    />

                                    <div className="flex justify-between mt-6">
                                        <button
                                            type="button"
                                            onClick={goBack}
                                            className="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-[.98] transition-all"
                                        >
                                            ← Anterior
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={submitting}
                                            className="rounded-xl bg-violet-600 px-8 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200 disabled:opacity-60 flex items-center gap-2"
                                        >
                                            {submitting && (
                                                <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                            )}
                                            Confirmar Pedido
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Order summary — always visible on desktop */}
                        <div className="hidden lg:block lg:col-span-1">
                            <OrderSummary cart={cart} />
                        </div>

                        {/* Order summary — mobile: show only on step 3 */}
                        {step === 3 && (
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
