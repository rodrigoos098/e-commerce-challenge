import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import PublicLayout from '@/Layouts/PublicLayout';
import type { ProfilePageProps, User } from '@/types/public';

// ——— Mock ——————————————————————————————————————————————————

const MOCK_USER: User = {
    id: 1,
    name: 'João da Silva',
    email: 'joao@exemplo.com',
};

// ——— Form field component ——————————————————————————————————

interface FieldProps {
    id: string;
    label: string;
    type?: string;
    value: string;
    onChange: (v: string) => void;
    error?: string;
    placeholder?: string;
    autoComplete?: string;
}

function Field({ id, label, type = 'text', value, onChange, error, placeholder, autoComplete }: FieldProps) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-semibold text-gray-700 mb-1.5">
                {label}
            </label>
            <input
                id={id}
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                autoComplete={autoComplete}
                aria-describedby={error ? `${id}-error` : undefined}
                className={`w-full rounded-xl border px-4 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                    ${error ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
            />
            {error && (
                <p id={`${id}-error`} role="alert" className="mt-1 text-xs text-red-600">{error}</p>
            )}
        </div>
    );
}

// ——— Page ————————————————————————————————————————————————

interface ProfileFormData {
    name: string;
    email: string;
}

interface PasswordFormData {
    current_password: string;
    password: string;
    password_confirmation: string;
}

export default function Profile({ user }: Partial<ProfilePageProps>) {
    const u = user ?? MOCK_USER;
    const [activeTab, setActiveTab] = useState<'profile' | 'password'>('profile');

    // Profile form
    const profileForm = useForm<ProfileFormData>({
        name: u.name,
        email: u.email,
    });

    const handleProfileSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.put('/customer/profile', {
            onSuccess: () => toast.success('Perfil atualizado com sucesso!'),
            onError: () => toast.error('Erro ao atualizar perfil.'),
        });
    };

    // Password form
    const passwordForm = useForm<PasswordFormData>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handlePasswordSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.put('/customer/profile/password', {
            onSuccess: () => {
                toast.success('Senha alterada com sucesso!');
                passwordForm.reset();
            },
            onError: () => toast.error('Erro ao alterar senha.'),
        });
    };

    return (
        <PublicLayout title="Meu Perfil">
            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-10">
                {/* Header */}
                <div className="mb-8 flex items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-lg">
                        <span className="text-2xl font-extrabold text-white">
                            {u.name.charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div>
                        <h1 className="text-2xl font-extrabold text-gray-900">{u.name}</h1>
                        <p className="text-sm text-gray-500">{u.email}</p>
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
                        <form onSubmit={handleProfileSubmit} className="space-y-5">
                            <Field
                                id="name"
                                label="Nome completo"
                                value={profileForm.data.name}
                                onChange={(v) => profileForm.setData('name', v)}
                                error={profileForm.errors.name}
                                autoComplete="name"
                            />
                            <Field
                                id="email"
                                label="E-mail"
                                type="email"
                                value={profileForm.data.email}
                                onChange={(v) => profileForm.setData('email', v)}
                                error={profileForm.errors.email}
                                autoComplete="email"
                            />
                            <div className="flex justify-end pt-2">
                                <button
                                    type="submit"
                                    disabled={profileForm.processing}
                                    className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200 disabled:opacity-60 flex items-center gap-2"
                                >
                                    {profileForm.processing && (
                                        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    )}
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
                        <form onSubmit={handlePasswordSubmit} className="space-y-5">
                            <Field
                                id="current_password"
                                label="Senha atual"
                                type="password"
                                value={passwordForm.data.current_password}
                                onChange={(v) => passwordForm.setData('current_password', v)}
                                error={passwordForm.errors.current_password}
                                autoComplete="current-password"
                                placeholder="Digite sua senha atual"
                            />
                            <Field
                                id="new_password"
                                label="Nova senha"
                                type="password"
                                value={passwordForm.data.password}
                                onChange={(v) => passwordForm.setData('password', v)}
                                error={passwordForm.errors.password}
                                autoComplete="new-password"
                                placeholder="Mínimo 8 caracteres"
                            />
                            <Field
                                id="password_confirmation"
                                label="Confirmar nova senha"
                                type="password"
                                value={passwordForm.data.password_confirmation}
                                onChange={(v) => passwordForm.setData('password_confirmation', v)}
                                error={passwordForm.errors.password_confirmation}
                                autoComplete="new-password"
                                placeholder="Repita a nova senha"
                            />
                            <div className="flex justify-end pt-2">
                                <button
                                    type="submit"
                                    disabled={passwordForm.processing}
                                    className="rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-md shadow-violet-200 disabled:opacity-60 flex items-center gap-2"
                                >
                                    {passwordForm.processing && (
                                        <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    )}
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
