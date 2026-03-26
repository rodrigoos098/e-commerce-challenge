import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import { z } from 'zod';
import Spinner from '@/Components/Shared/Spinner';
import Logo from '@/Components/Shared/Logo';

const loginSchema = z.object({
    email: z.string().min(1, 'E-mail obrigatório').email('E-mail inválido'),
    password: z.string().min(1, 'Senha obrigatória'),
});

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const [clientErrors, setClientErrors] = useState<Partial<Record<keyof LoginForm, string>>>({});
    const mergedErrors = { ...clientErrors, ...errors };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const result = loginSchema.safeParse(data);
        if (!result.success) {
            const fieldErrors: Partial<Record<keyof LoginForm, string>> = {};
            result.error.issues.forEach((issue: z.ZodIssue) => {
                const field = issue.path[0] as keyof LoginForm;
                if (!fieldErrors[field]) { fieldErrors[field] = issue.message; }
            });
            setClientErrors(fieldErrors);
            return;
        }

        setClientErrors({});
        post('/login', {
            onError: () => toast.error('Credenciais inválidas. Tente novamente.'),
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100 px-4 py-12">
            {/* Card */}
            <div className="w-full max-w-md">
                {/* Logo */}
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <Logo />
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-warm-700">Bem-vindo ao Shopsugi<span className="kintsugi-shimmer">ツ</span></h1>
                    <p className="mt-1 text-sm text-warm-500">Entre na sua conta para continuar.</p>
                </div>

                <div className="rounded-3xl bg-white shadow-xl border border-warm-200 p-8">
                    <form onSubmit={handleSubmit} noValidate className="space-y-5">
                        {/* Email */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-semibold text-warm-600 mb-1.5">
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => { setData('email', e.target.value); setClientErrors((p) => ({ ...p, email: undefined })); }}
                                placeholder="seu@email.com"
                                autoComplete="email"
                                aria-describedby={mergedErrors.email ? 'email-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.email ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.email && (
                                <p id="email-error" role="alert" className="mt-1.5 text-xs text-red-600">
                                    {mergedErrors.email}
                                </p>
                            )}
                        </div>

                        {/* Password */}
                        <div>
                            <div className="flex items-center justify-between mb-1.5">
                                <label htmlFor="password" className="block text-sm font-semibold text-warm-600">
                                    Senha
                                </label>
                                <a href="#" className="text-xs text-kintsugi-600 hover:text-kintsugi-700 transition-colors">
                                    Esqueceu a senha?
                                </a>
                            </div>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => { setData('password', e.target.value); setClientErrors((p) => ({ ...p, password: undefined })); }}
                                placeholder="••••••••"
                                autoComplete="current-password"
                                aria-describedby={mergedErrors.password ? 'password-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-kintsugi-500 focus:border-transparent transition-all
                                    ${mergedErrors.password ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'}`}
                            />
                            {mergedErrors.password && (
                                <p id="password-error" role="alert" className="mt-1.5 text-xs text-red-600">
                                    {mergedErrors.password}
                                </p>
                            )}
                        </div>

                        {/* Remember me */}
                        <div className="flex items-center gap-2">
                            <input
                                id="remember"
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-4 w-4 rounded border-warm-200 text-kintsugi-600 focus:ring-kintsugi-500"
                            />
                            <label htmlFor="remember" className="text-sm text-warm-600">
                                Lembrar de mim
                            </label>
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-kintsugi-500 py-3 text-sm font-bold text-white hover:bg-kintsugi-600 active:scale-[.98] transition-all shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            {processing && <Spinner />}
                            Entrar
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-warm-500">
                        Não tem uma conta?{' '}
                        <Link href="/register" className="font-semibold text-kintsugi-600 hover:text-kintsugi-700 transition-colors">
                            Criar conta grátis
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
