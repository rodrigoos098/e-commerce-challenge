import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import PublicLayout from '@/Layouts/PublicLayout';
import type { CheckoutPageProps, Cart } from '@/types/public';

const addressSchema = z.object({
    name: z.string().min(1, 'Nome obrigatório'),
    street: z.string().min(1, 'Endereço obrigatório'),
    city: z.string().min(1, 'Cidade obrigatória'),
    state: z.string().min(2, 'Estado obrigatório'),
    zip: z.string().min(8, 'CEP inválido'),
    country: z.string().min(1, 'País obrigatório'),
});

function buildCheckoutSchema(sameBilling: boolean) {
    const shipping = z.object({
        shipping_name: addressSchema.shape.name,
        shipping_street: addressSchema.shape.street,
        shipping_city: addressSchema.shape.city,
        shipping_state: addressSchema.shape.state,
        shipping_zip: addressSchema.shape.zip,
        shipping_country: addressSchema.shape.country,
    });
    if (sameBilling) { return shipping; }
    return shipping.merge(
        z.object({
            billing_name: addressSchema.shape.name,
            billing_street: addressSchema.shape.street,
            billing_city: addressSchema.shape.city,
            billing_state: addressSchema.shape.state,
            billing_zip: addressSchema.shape.zip,
            billing_country: addressSchema.shape.country,
        }),
    );
}

// ——— Mock ————————————————————————————————————————————————

const MOCK_CART: Cart = {
    id: 1,
    items: [
        {
            id: 1,
            product: {
                id: 1,
                name: 'Fone de Ouvido Bluetooth Premium',
                slug: 'fone-bluetooth',
                description: '',
                price: 299.9,
                quantity: 50,
                min_quantity: 5,
                active: true,
                category: { id: 1, name: 'Eletrônicos', slug: 'eletronicos', active: true, parent_id: null },
                tags: [],
                created_at: '',
                updated_at: '',
            },
            quantity: 2,
        },
    ],
    subtotal: 599.8,
    tax: 53.98,
    shipping_cost: 15.0,
    total: 668.78,
    item_count: 2,
};

// ——— Address Section ———————————————————————————————————————

interface AddressSectionProps {
    prefix: string;
    values: Record<string, string>;
    errors: Partial<Record<string, string>>;
    onChange: (field: string, value: string) => void;
}

function AddressSection({ prefix, values, errors, onChange }: AddressSectionProps) {
    const field = (name: string) => `${prefix}_${name}`;

    const input = (name: string, label: string, placeholder: string, type = 'text', colSpan = 'col-span-2') => (
        <div className={colSpan}>
            <label htmlFor={field(name)} className="block text-sm font-semibold text-gray-700 mb-1.5">
                {label}
            </label>
            <input
                id={field(name)}
                type={type}
                value={values[field(name)] ?? ''}
                onChange={(e) => onChange(field(name), e.target.value)}
                placeholder={placeholder}
                className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                    ${errors[field(name)] ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
            />
            {errors[field(name)] && (
                <p role="alert" className="mt-1 text-xs text-red-600">{errors[field(name)]}</p>
            )}
        </div>
    );

    return (
        <div className="grid grid-cols-2 gap-4">
            {input('name', 'Nome do destinatário', 'João da Silva')}
            {input('street', 'Endereço', 'Rua Exemplo, 123')}
            {input('city', 'Cidade', 'São Paulo', 'text', 'col-span-1')}
            {input('state', 'Estado', 'SP', 'text', 'col-span-1')}
            {input('zip', 'CEP', '01310-100', 'text', 'col-span-1')}
            {input('country', 'País', 'Brasil', 'text', 'col-span-1')}
        </div>
    );
}

// ——— Page ————————————————————————————————————————————————

interface CheckoutFormData {
    shipping_name: string;
    shipping_street: string;
    shipping_city: string;
    shipping_state: string;
    shipping_zip: string;
    shipping_country: string;
    billing_name: string;
    billing_street: string;
    billing_city: string;
    billing_state: string;
    billing_zip: string;
    billing_country: string;
    same_billing: boolean;
    notes: string;
}

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export default function Checkout({ cart }: Partial<CheckoutPageProps>) {
    const c = cart ?? MOCK_CART;
    const [sameBilling, setSameBilling] = useState(true);

    const { data, setData, post, processing, errors } = useForm<CheckoutFormData>({
        shipping_name: '',
        shipping_street: '',
        shipping_city: '',
        shipping_state: '',
        shipping_zip: '',
        shipping_country: 'Brasil',
        billing_name: '',
        billing_street: '',
        billing_city: '',
        billing_state: '',
        billing_zip: '',
        billing_country: 'Brasil',
        same_billing: true,
        notes: '',
    });

    const [clientErrors, setClientErrors] = useState<Partial<Record<string, string>>>({});
    const mergedErrors = { ...clientErrors, ...(errors as Partial<Record<string, string>>) };

    const handleChange = (field: string, value: string) => {
        setData(field as keyof CheckoutFormData, value);
        setClientErrors((p) => ({ ...p, [field]: undefined }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const schema = buildCheckoutSchema(sameBilling);
        const result = schema.safeParse(data);
        if (!result.success) {
            const fieldErrors: Partial<Record<string, string>> = {};
            result.error.issues.forEach((issue: z.ZodIssue) => {
                const field = String(issue.path[0]);
                if (!fieldErrors[field]) { fieldErrors[field] = issue.message; }
            });
            setClientErrors(fieldErrors);
            toast.error('Preencha todos os campos obrigatórios.');
            return;
        }

        setClientErrors({});
        post('/customer/orders', {
            onSuccess: () => toast.success('Pedido realizado com sucesso!'),
            onError: () => toast.error('Erro ao finalizar pedido. Verifique os dados.'),
        });
    };

    return (
        <PublicLayout title="Checkout">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10">
                <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-8">Finalizar Compra</h1>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Form */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Shipping */}
                            <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
                                <h2 className="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">1</span>
                                    Endereço de Entrega
                                </h2>
                                <AddressSection
                                    prefix="shipping"
                                    values={data as unknown as Record<string, string>}
                                    errors={mergedErrors}
                                    onChange={handleChange}
                                />
                            </div>

                            {/* Billing */}
                            <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
                                <div className="flex items-center justify-between mb-5">
                                    <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">2</span>
                                        Endereço de Cobrança
                                    </h2>
                                    <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={sameBilling}
                                            onChange={(e) => {
                                                setSameBilling(e.target.checked);
                                                setData('same_billing', e.target.checked);
                                            }}
                                            className="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                        />
                                        Mesmo que entrega
                                    </label>
                                </div>
                                {!sameBilling && (
                                    <AddressSection
                                        prefix="billing"
                                        values={data as unknown as Record<string, string>}
                                        errors={mergedErrors}
                                        onChange={handleChange}
                                    />
                                )}
                                {sameBilling && (
                                    <p className="text-sm text-gray-400 italic">Usando o mesmo endereço de entrega.</p>
                                )}
                            </div>

                            {/* Notes */}
                            <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
                                <h2 className="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">3</span>
                                    Observações (opcional)
                                </h2>
                                <textarea
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Instruções especiais para entrega, etc."
                                    rows={3}
                                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent resize-none"
                                />
                            </div>
                        </div>

                        {/* Order summary */}
                        <div className="lg:col-span-1">
                            <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sticky top-24">
                                <h2 className="text-base font-bold text-gray-900 mb-5">Resumo do pedido</h2>

                                {/* Items */}
                                <div className="space-y-3 mb-5">
                                    {c.items.map((item) => (
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
                                        <span>Subtotal</span>
                                        <span>{formatPrice(c.subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600">
                                        <span>Impostos</span>
                                        <span>{formatPrice(c.tax)}</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600">
                                        <span>Frete</span>
                                        <span>{c.shipping_cost === 0 ? 'Grátis' : formatPrice(c.shipping_cost)}</span>
                                    </div>
                                    <div className="flex justify-between font-bold text-base text-gray-900 pt-2 border-t border-gray-100">
                                        <span>Total</span>
                                        <span>{formatPrice(c.total)}</span>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="mt-6 w-full rounded-2xl bg-violet-600 py-3.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-lg shadow-violet-200 disabled:opacity-60 flex items-center justify-center gap-2"
                                >
                                    {processing && (
                                        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    )}
                                    Confirmar Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </PublicLayout>
    );
}
