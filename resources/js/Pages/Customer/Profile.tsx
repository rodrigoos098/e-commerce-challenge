import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { Resolver } from 'react-hook-form';
import { router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import PublicLayout from '@/Layouts/PublicLayout';
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

function Field({ id, label, type = 'text', error, placeholder, autoComplete, registration }: FieldProps) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-semibold text-gray-700 mb-1.5">
                {label}
            </label>
            <input
                id={id}
                type={type}
                placeholder={placeholder}
                autoComplete={autoComplete}
                aria-describedby={error ? `${id}-error` : undefined}
                className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                    ${error ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                {...registration}
            />
            {error && (
                <p id={`${id}-error`} role="alert" className="mt-1 text-xs text-red-600">{error}</p>
            )}
        </div>
    );
}

function SpinnerIcon() {
    return (
        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
    );
}

// ——— Page ————————————————————————————————————————————————

export default function Profile({ user }: ProfilePageProps) {
    const [activeTab, setActiveTab] = useState<'profile' | 'password'>('profile');
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
                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-lg">
                        <span className="text-2xl font-extrabold text-white">
                            {user.name.charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div>
                        <h1 className="text-2xl font-extrabold text-gray-900">{user.name}</h1>
                        <p className="text-sm text-gray-500">{user.email}</p>
                    </div>
                </div>

                {/* Tabs */}
                <div className="border-b border-gray-200 mb-8">
                    <nav className="-mb-px flex gap-6" aria-label="Seções do perfil">
                        {[
                            { key: 'profile' as const, label: 'Informações' },
                            { key: 'password' as const, label: 'Segurança' },
                        ].map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() => setActiveTab(tab.key)}
                                aria-selected={activeTab === tab.key}
                                role="tab"
                                className={`pb-3 text-sm font-semibold border-b-2 transition-colors ${
                                    activeTab === tab.key
                                        ? 'border-violet-600 text-violet-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </nav>
                </div>

                {/* Profile tab */}
                {activeTab === 'profile' && (
                    <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                        <h2 className="text-base font-bold text-gray-900 mb-6">Informações Pessoais</h2>
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
                                    className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200 disabled:opacity-60 flex items-center gap-2"
                                >
                                    {profileSubmitting && <SpinnerIcon />}
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Password tab */}
                {activeTab === 'password' && (
                    <div className="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-8">
                        <h2 className="text-base font-bold text-gray-900 mb-6">Alterar Senha</h2>
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
                                    className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200 disabled:opacity-60 flex items-center gap-2"
                                >
                                    {passwordSubmitting && <SpinnerIcon />}
                                    Alterar Senha
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
