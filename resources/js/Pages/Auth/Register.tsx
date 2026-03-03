import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';

const registerSchema = z.object({
    name: z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
    email: z.string().min(1, 'E-mail obrigatório').email('E-mail inválido'),
    password: z.string().min(8, 'Senha deve ter ao menos 8 caracteres'),
    password_confirmation: z.string().min(1, 'Confirmação obrigatória'),
}).refine((d) => d.password === d.password_confirmation, {
    message: 'As senhas não conferem',
    path: ['password_confirmation'],
});

interface RegisterForm {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [clientErrors, setClientErrors] = useState<Partial<Record<keyof RegisterForm, string>>>({});
    const mergedErrors = { ...clientErrors, ...errors };

    const clearField = (field: keyof RegisterForm) =>
        setClientErrors((p) => ({ ...p, [field]: undefined }));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const result = registerSchema.safeParse(data);
        if (!result.success) {
            const fieldErrors: Partial<Record<keyof RegisterForm, string>> = {};
            result.error.issues.forEach((issue: z.ZodIssue) => {
                const field = issue.path[0] as keyof RegisterForm;
                if (!fieldErrors[field]) { fieldErrors[field] = issue.message; }
            });
            setClientErrors(fieldErrors);
            return;
        }

        setClientErrors({});
        post('/register', {
            onError: () => toast.error('Erro ao criar conta. Verifique os campos e tente novamente.'),
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-50 via-white to-indigo-50 px-4 py-12">
            <div className="w-full max-w-md">
                {/* Logo */}
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 shadow-lg">
                            <span className="text-white font-extrabold text-xl leading-none">E</span>
                        </div>
                        <span className="text-2xl font-extrabold text-gray-900">
                            E<span className="text-violet-600">Shop</span>
                        </span>
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-gray-900">Crie sua conta</h1>
                    <p className="mt-1 text-sm text-gray-500">É rápido, grátis e sem compromisso!</p>
                </div>

                <div className="rounded-3xl bg-white shadow-xl border border-gray-100 p-8">
                    <form onSubmit={handleSubmit} noValidate className="space-y-5">
                        {/* Name */}
                        <div>
                            <label htmlFor="name" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                Nome completo
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => { setData('name', e.target.value); clearField('name'); }}
                                placeholder="João da Silva"
                                autoComplete="name"
                                aria-describedby={mergedErrors.name ? 'name-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${mergedErrors.name ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {mergedErrors.name && (
                                <p id="name-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.name}</p>
                            )}
                        </div>

                        {/* Email */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => { setData('email', e.target.value); clearField('email'); }}
                                placeholder="seu@email.com"
                                autoComplete="email"
                                aria-describedby={mergedErrors.email ? 'email-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${mergedErrors.email ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {mergedErrors.email && (
                                <p id="email-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.email}</p>
                            )}
                        </div>

                        {/* Password */}
                        <div>
                            <label htmlFor="password" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                Senha
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => { setData('password', e.target.value); clearField('password'); }}
                                placeholder="Mínimo 8 caracteres"
                                autoComplete="new-password"
                                aria-describedby={mergedErrors.password ? 'password-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${mergedErrors.password ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {mergedErrors.password && (
                                <p id="password-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.password}</p>
                            )}
                        </div>

                        {/* Confirm password */}
                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                Confirmar senha
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => { setData('password_confirmation', e.target.value); clearField('password_confirmation'); }}
                                placeholder="Repita a senha"
                                autoComplete="new-password"
                                aria-describedby={mergedErrors.password_confirmation ? 'pw-confirm-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${mergedErrors.password_confirmation ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {mergedErrors.password_confirmation && (
                                <p id="pw-confirm-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.password_confirmation}</p>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-violet-600 py-3 text-sm font-bold text-white hover:bg-violet-700 active:scale-[.98] transition-all shadow-lg shadow-violet-200 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            {processing && (
                                <svg className="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            )}
                            Criar conta
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-gray-500">
                        Já tem uma conta?{' '}
                        <Link href="/login" className="font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                            Fazer login
                        </Link>
                    </p>
                </div>

                <p className="mt-6 text-center text-xs text-gray-400">
                    <Link href="/" className="hover:text-violet-600 transition-colors">← Voltar para a loja</Link>
                </p>
            </div>
        </div>
    );
}
