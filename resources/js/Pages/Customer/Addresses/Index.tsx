import React, { useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { Resolver } from 'react-hook-form';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import Modal from '@/Components/Shared/Modal';
import PublicLayout from '@/Layouts/PublicLayout';
import Spinner from '@/Components/Shared/Spinner';
import type { CustomerAddressesPageProps, SavedAddress } from '@/types/public';
import { appRoutes } from '@/utils/routes';

const addressSchema = z.object({
  label: z.string().trim().min(1, 'Informe um apelido para o endereço'),
  recipient_name: z.string().trim().min(1, 'Informe o nome do destinatário'),
  street: z.string().trim().min(1, 'Informe a rua e número'),
  city: z.string().trim().min(1, 'Informe a cidade'),
  state: z.string().trim().min(1, 'Informe o estado'),
  zip_code: z.string().trim().min(1, 'Informe o CEP'),
  country: z.string().trim().min(1, 'Informe o país'),
  is_default_shipping: z.boolean(),
  is_default_billing: z.boolean(),
});

type AddressFormData = z.infer<typeof addressSchema>;

const defaultValues: AddressFormData = {
  label: '',
  recipient_name: '',
  street: '',
  city: '',
  state: '',
  zip_code: '',
  country: 'Brasil',
  is_default_shipping: false,
  is_default_billing: false,
};

function formatAddress(address: SavedAddress): string {
  return `${address.street}, ${address.city} - ${address.state}, ${address.zip_code}`;
}

interface FieldProps {
  id: keyof AddressFormData;
  label: string;
  register: ReturnType<typeof useForm<AddressFormData>>['register'];
  error?: string;
  placeholder: string;
}

function Field({ id, label, register, error, placeholder }: FieldProps) {
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-semibold text-warm-600">
        {label}
      </label>
      <input
        id={id}
        {...register(id)}
        placeholder={placeholder}
        className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-warm-400 transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500 ${error ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
      />
      {error && (
        <p role="alert" className="mt-1 text-xs text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}

export default function CustomerAddressesIndex({ addresses }: CustomerAddressesPageProps) {
  const [editingAddress, setEditingAddress] = useState<SavedAddress | null>(null);
  const [deleteAddress, setDeleteAddress] = useState<SavedAddress | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const defaultShippingAddress = useMemo(
    () => addresses.find((address) => address.is_default_shipping) ?? null,
    [addresses]
  );
  const defaultBillingAddress = useMemo(
    () => addresses.find((address) => address.is_default_billing) ?? null,
    [addresses]
  );

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<AddressFormData>({
    resolver: zodResolver(addressSchema) as Resolver<AddressFormData>,
    defaultValues,
  });

  const startCreate = () => {
    setEditingAddress(null);
    reset(defaultValues);
  };

  const startEdit = (address: SavedAddress) => {
    setEditingAddress(address);
    reset({
      label: address.label,
      recipient_name: address.recipient_name,
      street: address.street,
      city: address.city,
      state: address.state,
      zip_code: address.zip_code,
      country: address.country,
      is_default_shipping: address.is_default_shipping,
      is_default_billing: address.is_default_billing,
    });
  };

  const onSubmit = (data: AddressFormData) => {
    setSubmitting(true);

    const options = {
      onSuccess: () => {
        toast.success(
          editingAddress ? 'Endereço atualizado com sucesso!' : 'Endereço criado com sucesso!'
        );
        setEditingAddress(null);
        reset(defaultValues);
      },
      onError: () => toast.error('Não foi possível salvar o endereço.'),
      onFinish: () => setSubmitting(false),
    };

    if (editingAddress) {
      router.put(appRoutes.customer.addresses.update(editingAddress.id), data, options);

      return;
    }

    router.post(appRoutes.customer.addresses.store, data, options);
  };

  const handleDelete = (address: SavedAddress) => {
    setDeleteAddress(address);
  };

  const confirmDelete = () => {
    if (!deleteAddress) {
      return;
    }

    setDeleting(true);

    router.delete(appRoutes.customer.addresses.destroy(deleteAddress.id), {
      onSuccess: () => toast.success('Endereço removido com sucesso!'),
      onError: () => toast.error('Não foi possível remover o endereço.'),
      onFinish: () => {
        setDeleting(false);
        setDeleteAddress(null);
      },
    });
  };

  const setDefault = (address: SavedAddress, type: 'shipping' | 'billing') => {
    router.put(
      appRoutes.customer.addresses.setDefault(address.id, type),
      {},
      {
        onSuccess: () =>
          toast.success(
            type === 'shipping' ? 'Padrão de entrega atualizado!' : 'Padrão de cobrança atualizado!'
          ),
        onError: () => toast.error('Não foi possível atualizar o endereço padrão.'),
      }
    );
  };

  return (
    <PublicLayout title="Meus Endereços">
      <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-kintsugi-600">
              Área do cliente
            </p>
            <h1 className="mt-2 text-3xl font-extrabold text-warm-700">Meus Endereços</h1>
            <p className="mt-2 max-w-2xl text-sm text-warm-500">
              Cadastre endereços de entrega e cobrança para reutilizar no checkout sem perder o
              histórico dos pedidos já realizados.
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            <Link
              href={appRoutes.customer.profile}
              className="rounded-xl border border-warm-200 px-4 py-2.5 text-sm font-semibold text-warm-600 transition-all hover:bg-warm-50"
            >
              Voltar ao Perfil
            </Link>
            <button
              type="button"
              onClick={startCreate}
              className="rounded-xl bg-kintsugi-500 px-4 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600"
            >
              Novo Endereço
            </button>
          </div>
        </div>

        <div className="mt-8 grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
          <section className="space-y-4" aria-label="Lista de endereços salvos">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="rounded-2xl border border-warm-200 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-warm-400">
                  Entrega padrão
                </p>
                <p className="mt-3 text-lg font-bold text-warm-700">
                  {defaultShippingAddress?.label ?? 'Não definido'}
                </p>
                <p className="mt-1 text-sm text-warm-500">
                  {defaultShippingAddress
                    ? formatAddress(defaultShippingAddress)
                    : 'Defina um endereço para agilizar o checkout.'}
                </p>
              </div>
              <div className="rounded-2xl border border-warm-200 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-warm-400">
                  Cobrança padrão
                </p>
                <p className="mt-3 text-lg font-bold text-warm-700">
                  {defaultBillingAddress?.label ?? 'Não definido'}
                </p>
                <p className="mt-1 text-sm text-warm-500">
                  {defaultBillingAddress
                    ? formatAddress(defaultBillingAddress)
                    : 'Defina um endereço de cobrança preferencial.'}
                </p>
              </div>
            </div>

            {addresses.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-warm-300 bg-white p-8 text-center shadow-sm">
                <h2 className="text-lg font-bold text-warm-700">Nenhum endereço cadastrado</h2>
                <p className="mt-2 text-sm text-warm-500">
                  Adicione seu primeiro endereço para reutilizar no checkout e definir padrões de
                  entrega e cobrança.
                </p>
              </div>
            ) : (
              addresses.map((address) => (
                <article
                  key={address.id}
                  className="rounded-2xl border border-warm-200 bg-white p-5 shadow-sm"
                >
                  <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-lg font-bold text-warm-700">{address.label}</h2>
                        {address.is_default_shipping && (
                          <span className="rounded-full bg-kintsugi-100 px-2.5 py-1 text-[11px] font-semibold text-kintsugi-700">
                            Entrega padrão
                          </span>
                        )}
                        {address.is_default_billing && (
                          <span className="rounded-full bg-warm-200 px-2.5 py-1 text-[11px] font-semibold text-warm-700">
                            Cobrança padrão
                          </span>
                        )}
                      </div>
                      <p className="mt-3 text-sm font-semibold text-warm-600">
                        {address.recipient_name}
                      </p>
                      <p className="mt-1 text-sm text-warm-500">{formatAddress(address)}</p>
                      <p className="text-sm text-warm-500">{address.country}</p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                      {!address.is_default_shipping && (
                        <button
                          type="button"
                          onClick={() => setDefault(address, 'shipping')}
                          className="rounded-xl border border-kintsugi-200 px-3 py-2 text-xs font-semibold text-kintsugi-700 transition-all hover:bg-kintsugi-50"
                        >
                          Tornar entrega padrão
                        </button>
                      )}
                      {!address.is_default_billing && (
                        <button
                          type="button"
                          onClick={() => setDefault(address, 'billing')}
                          className="rounded-xl border border-warm-200 px-3 py-2 text-xs font-semibold text-warm-700 transition-all hover:bg-warm-50"
                        >
                          Tornar cobrança padrão
                        </button>
                      )}
                      <button
                        type="button"
                        onClick={() => startEdit(address)}
                        className="rounded-xl border border-warm-200 px-3 py-2 text-xs font-semibold text-warm-700 transition-all hover:bg-warm-50"
                      >
                        Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(address)}
                        className="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition-all hover:bg-red-50"
                      >
                        Excluir
                      </button>
                    </div>
                  </div>
                </article>
              ))
            )}
          </section>

          <section
            className="rounded-3xl border border-warm-200 bg-white p-6 shadow-sm sm:p-8"
            aria-label="Formulário de endereço"
          >
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 className="text-xl font-bold text-warm-700">
                  {editingAddress ? 'Editar endereço' : 'Cadastrar endereço'}
                </h2>
                <p className="mt-1 text-sm text-warm-500">
                  {editingAddress
                    ? 'Atualize os dados e, se quiser, altere seus padrões.'
                    : 'Preencha os dados para salvar um novo endereço no seu cadastro.'}
                </p>
              </div>
              {editingAddress && (
                <button
                  type="button"
                  onClick={startCreate}
                  className="text-sm font-semibold text-kintsugi-600 underline underline-offset-4"
                >
                  Cancelar edição
                </button>
              )}
            </div>

            <form onSubmit={handleSubmit(onSubmit)} className="mt-6 space-y-5">
              <Field
                id="label"
                label="Apelido"
                register={register}
                error={errors.label?.message}
                placeholder="Casa, trabalho, apartamento..."
              />
              <Field
                id="recipient_name"
                label="Destinatário"
                register={register}
                error={errors.recipient_name?.message}
                placeholder="Nome completo"
              />
              <Field
                id="street"
                label="Rua e número"
                register={register}
                error={errors.street?.message}
                placeholder="Rua Exemplo, 123"
              />

              <div className="grid gap-5 sm:grid-cols-2">
                <Field
                  id="city"
                  label="Cidade"
                  register={register}
                  error={errors.city?.message}
                  placeholder="São Paulo"
                />
                <Field
                  id="state"
                  label="Estado"
                  register={register}
                  error={errors.state?.message}
                  placeholder="SP"
                />
                <Field
                  id="zip_code"
                  label="CEP"
                  register={register}
                  error={errors.zip_code?.message}
                  placeholder="01310-100"
                />
                <Field
                  id="country"
                  label="País"
                  register={register}
                  error={errors.country?.message}
                  placeholder="Brasil"
                />
              </div>

              <div className="grid gap-3 rounded-2xl border border-warm-200 bg-warm-50 p-4">
                <label className="flex items-center gap-3 text-sm font-medium text-warm-700">
                  <input
                    type="checkbox"
                    {...register('is_default_shipping')}
                    className="h-4 w-4 rounded border-warm-300 text-kintsugi-500 focus:ring-kintsugi-500"
                  />
                  Definir como endereço padrão de entrega
                </label>
                <label className="flex items-center gap-3 text-sm font-medium text-warm-700">
                  <input
                    type="checkbox"
                    {...register('is_default_billing')}
                    className="h-4 w-4 rounded border-warm-300 text-kintsugi-500 focus:ring-kintsugi-500"
                  />
                  Definir como endereço padrão de cobrança
                </label>
              </div>

              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={submitting}
                  className="flex items-center gap-2 rounded-xl bg-kintsugi-500 px-6 py-3 text-sm font-bold text-white transition-all hover:bg-kintsugi-600 disabled:opacity-60"
                >
                  {submitting && <Spinner />}
                  {editingAddress ? 'Salvar alterações' : 'Salvar endereço'}
                </button>
              </div>
            </form>
          </section>
        </div>
      </div>

      <Modal
        isOpen={deleteAddress !== null}
        onClose={() => {
          if (!deleting) {
            setDeleteAddress(null);
          }
        }}
        title="Excluir endereço"
        onConfirm={confirmDelete}
        confirmLabel="Excluir endereço"
        confirmDestructive
        loading={deleting}
      >
        <p className="text-sm leading-relaxed text-warm-600">
          O endereço <strong className="font-semibold">{deleteAddress?.label}</strong> será removido
          do seu cadastro.
        </p>
      </Modal>
    </PublicLayout>
  );
}
