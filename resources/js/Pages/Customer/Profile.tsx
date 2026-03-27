import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { Resolver } from 'react-hook-form';
import { Link, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import PublicLayout from '@/Layouts/PublicLayout';
import Spinner from '@/Components/Shared/Spinner';
import type { ProfilePageProps } from '@/types/public';

// ——— Schemas ——————————————————————————————————————————————

const profileSchema = z.object({
  name: z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
  email: z.string().email('E-mail inválido'),
});

const passwordSchema = z
  .object({
    current_password: z.string().min(1, 'Senha atual obrigatória'),
    password: z.string().min(8, 'Nova senha deve ter ao menos 8 caracteres'),
    password_confirmation: z.string().min(1, 'Confirmação obrigatória'),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'As senhas não conferem',
    path: ['password_confirmation'],
  });

type ProfileFormData = z.infer<typeof profileSchema>;
type PasswordFormData = z.infer<typeof passwordSchema>;

// ——— Form field component ——————————————————————————————————

interface FieldProps {
  id: string;
  label: string;
  type?: string;
  error?: string;
  placeholder?: string;
  autoComplete?: string;
  registration: ReturnType<ReturnType<typeof useForm>['register']>;
}

function Field({
  id,
  label,
  type = 'text',
  error,
  placeholder,
  autoComplete,
  registration,
}: FieldProps) {
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-semibold text-warm-600 mb-1.5">
        {label}
      </label>
      <input
        id={id}
        type={type}
        placeholder={placeholder}
        autoComplete={autoComplete}
        aria-describedby={error ? `${id}-error` : undefined}
        className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                    ${error ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
        {...registration}
      />
      {error && (
        <p id={`${id}-error`} role="alert" className="mt-1 text-xs text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}

// ——— Page ————————————————————————————————————————————————

export default function Profile({ user, address_summary }: ProfilePageProps) {
  const [activeTab, setActiveTab] = useState<'profile' | 'password' | 'addresses'>('profile');
  const [profileSubmitting, setProfileSubmitting] = useState(false);
  const [passwordSubmitting, setPasswordSubmitting] = useState(false);

  // Profile form
  const {
    register: registerProfile,
    handleSubmit: handleProfileSubmit,
    formState: { errors: profileErrors },
  } = useForm<ProfileFormData>({
    resolver: zodResolver(profileSchema) as Resolver<ProfileFormData>,
    defaultValues: { name: user.name, email: user.email },
  });

  const onProfileSubmit = (data: ProfileFormData) => {
    setProfileSubmitting(true);
    router.put('/customer/profile', data, {
      onSuccess: () => toast.success('Perfil atualizado com sucesso!'),
      onError: () => toast.error('Erro ao atualizar perfil.'),
      onFinish: () => setProfileSubmitting(false),
    });
  };

  // Password form
  const {
    register: registerPassword,
    handleSubmit: handlePasswordSubmit,
    formState: { errors: passwordErrors },
    reset: resetPassword,
  } = useForm<PasswordFormData>({
    resolver: zodResolver(passwordSchema) as Resolver<PasswordFormData>,
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  });

  const onPasswordSubmit = (data: PasswordFormData) => {
    setPasswordSubmitting(true);
    router.put('/customer/profile/password', data, {
      onSuccess: () => {
        toast.success('Senha alterada com sucesso!');
        resetPassword();
      },
      onError: () => toast.error('Erro ao alterar senha.'),
      onFinish: () => setPasswordSubmitting(false),
    });
  };

  return (
    <PublicLayout title="Meu Perfil">
      <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-10">
        {/* Header */}
        <div className="mb-8 flex items-center gap-4">
          <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-kintsugi-500 to-kintsugi-600 shadow-lg">
            <span className="text-2xl font-extrabold text-white">
              {user.name.charAt(0).toUpperCase()}
            </span>
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-warm-700">{user.name}</h1>
            <p className="text-sm text-warm-500">{user.email}</p>
          </div>
        </div>

        {/* Tabs */}
        <div className="border-b border-warm-200 mb-8">
          <nav className="-mb-px flex gap-6" aria-label="Seções do perfil">
            {[
              { key: 'profile' as const, label: 'Informações' },
              { key: 'password' as const, label: 'Segurança' },
              { key: 'addresses' as const, label: 'Endereços' },
            ].map((tab) => (
              <button
                key={tab.key}
                type="button"
                onClick={() => setActiveTab(tab.key)}
                aria-selected={activeTab === tab.key}
                role="tab"
                className={`pb-3 text-sm font-semibold border-b-2 transition-colors ${
                  activeTab === tab.key
                    ? 'border-kintsugi-600 text-kintsugi-600'
                    : 'border-transparent text-warm-500 hover:text-warm-600 hover:border-warm-400'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Profile tab */}
        {activeTab === 'profile' && (
          <div className="rounded-2xl bg-white border border-warm-200 shadow-sm p-6 sm:p-8">
            <h2 className="text-base font-bold text-warm-700 mb-6">Informações Pessoais</h2>
            <form onSubmit={handleProfileSubmit(onProfileSubmit)} className="space-y-5">
              <Field
                id="name"
                label="Nome completo"
                error={profileErrors.name?.message}
                autoComplete="name"
                registration={registerProfile('name')}
              />
              <Field
                id="email"
                label="E-mail"
                type="email"
                error={profileErrors.email?.message}
                autoComplete="email"
                registration={registerProfile('email')}
              />
              <div className="flex justify-end pt-2">
                <button
                  type="submit"
                  disabled={profileSubmitting}
                  className="rounded-xl bg-kintsugi-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-md shadow-kintsugi-200 disabled:opacity-60 flex items-center gap-2"
                >
                  {profileSubmitting && <Spinner />}
                  Salvar Alterações
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Password tab */}
        {activeTab === 'password' && (
          <div className="rounded-2xl bg-white border border-warm-200 shadow-sm p-6 sm:p-8">
            <h2 className="text-base font-bold text-warm-700 mb-6">Alterar Senha</h2>
            <form onSubmit={handlePasswordSubmit(onPasswordSubmit)} className="space-y-5">
              <Field
                id="current_password"
                label="Senha atual"
                type="password"
                error={passwordErrors.current_password?.message}
                autoComplete="current-password"
                placeholder="Digite sua senha atual"
                registration={registerPassword('current_password')}
              />
              <Field
                id="new_password"
                label="Nova senha"
                type="password"
                error={passwordErrors.password?.message}
                autoComplete="new-password"
                placeholder="Mínimo 8 caracteres"
                registration={registerPassword('password')}
              />
              <Field
                id="password_confirmation"
                label="Confirmar nova senha"
                type="password"
                error={passwordErrors.password_confirmation?.message}
                autoComplete="new-password"
                placeholder="Repita a nova senha"
                registration={registerPassword('password_confirmation')}
              />
              <div className="flex justify-end pt-2">
                <button
                  type="submit"
                  disabled={passwordSubmitting}
                  className="rounded-xl bg-kintsugi-500 px-6 py-2.5 text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-md shadow-kintsugi-200 disabled:opacity-60 flex items-center gap-2"
                >
                  {passwordSubmitting && <Spinner />}
                  Alterar Senha
                </button>
              </div>
            </form>
          </div>
        )}

        {activeTab === 'addresses' && (
          <div className="rounded-2xl bg-white border border-warm-200 shadow-sm p-6 sm:p-8">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <h2 className="text-base font-bold text-warm-700">Meus Endereços</h2>
                <p className="mt-1 text-sm text-warm-500">
                  Gerencie endereços de entrega e cobrança para agilizar seu checkout.
                </p>
              </div>
              <Link
                href="/customer/addresses"
                className="inline-flex items-center justify-center rounded-xl bg-kintsugi-500 px-5 py-2.5 text-sm font-bold text-white transition-all hover:bg-kintsugi-600"
              >
                Gerenciar Endereços
              </Link>
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-3">
              <div className="rounded-2xl border border-warm-200 bg-warm-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-warm-400">
                  Total salvo
                </p>
                <p className="mt-2 text-2xl font-bold text-warm-700">{address_summary.count}</p>
              </div>
              <div className="rounded-2xl border border-warm-200 bg-warm-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-warm-400">
                  Entrega padrão
                </p>
                <p className="mt-2 text-sm font-semibold text-warm-700">
                  {address_summary.default_shipping_label ?? 'Não definido'}
                </p>
              </div>
              <div className="rounded-2xl border border-warm-200 bg-warm-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-warm-400">
                  Cobrança padrão
                </p>
                <p className="mt-2 text-sm font-semibold text-warm-700">
                  {address_summary.default_billing_label ?? 'Não definido'}
                </p>
              </div>
            </div>

            <div className="mt-6 rounded-2xl border border-dashed border-kintsugi-200 bg-kintsugi-50/60 p-4 text-sm text-kintsugi-700">
              Seus pedidos continuam salvando um snapshot do endereço usado no checkout, mesmo que
              você altere o cadastro depois.
            </div>
          </div>
        )}
      </div>
    </PublicLayout>
  );
}
