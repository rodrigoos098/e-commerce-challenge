import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import Spinner from '@/Components/Shared/Spinner';
import Logo from '@/Components/Shared/Logo';

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
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100 px-4 py-12">
            <div className="w-full max-w-md">
                {/* Logo */}
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <Logo />
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-warm-700">Junte-se ao Shopsugi<span className="kintsugi-shimmer">ツ</span></h1>
                    <p className="mt-1 text-sm text-warm-500">Descubra arte artesanal unica de todo o Brasil.</p>
                </div>

                <div className="rounded-3xl bg-white shadow-xl border border-warm-200 p-8">
                    <form onSubmit={handleSubmit} noValidate className="space-y-5">
                        {/* Name */}
                        <div>
                            <label htmlFor="name" className="block text-sm font-semibold text-warm-600 mb-1.5">
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
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.name ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.name && (
                                <p id="name-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.name}</p>
                            )}
                        </div>

                        {/* Email */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-semibold text-warm-600 mb-1.5">
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
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.email ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.email && (
                                <p id="email-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.email}</p>
                            )}
                        </div>

                        {/* Password */}
                        <div>
                            <label htmlFor="password" className="block text-sm font-semibold text-warm-600 mb-1.5">
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
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.password ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.password && (
                                <p id="password-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.password}</p>
                            )}
                        </div>

                        {/* Confirm password */}
                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-semibold text-warm-600 mb-1.5">
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
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.password_confirmation ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.password_confirmation && (
                                <p id="pw-confirm-error" role="alert" className="mt-1.5 text-xs text-red-600">{mergedErrors.password_confirmation}</p>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-kintsugi-500 py-3 text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            {processing && <Spinner />}
                            Criar conta
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-warm-500">
                        Já tem uma conta?{' '}
                        <Link href="/login" className="font-semibold text-kintsugi-600 hover:text-kintsugi-700 transition-colors">
                            Fazer login
                        </Link>
                    </p>
                </div>

                <p className="mt-6 text-center text-xs text-warm-400">
                    <Link href="/" className="hover:text-kintsugi-600 transition-colors">← Voltar para a loja</Link>
                </p>
            </div>
        </div>
    );
}
