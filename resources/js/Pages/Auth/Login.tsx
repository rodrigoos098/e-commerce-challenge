import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';

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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onError: () => toast.error('Credenciais inválidas. Tente novamente.'),
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-50 via-white to-indigo-50 px-4 py-12">
            {/* Card */}
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
                    <h1 className="mt-6 text-2xl font-bold text-gray-900">Bem-vindo de volta!</h1>
                    <p className="mt-1 text-sm text-gray-500">Entre na sua conta para continuar.</p>
                </div>

                <div className="rounded-3xl bg-white shadow-xl border border-gray-100 p-8">
                    <form onSubmit={handleSubmit} noValidate className="space-y-5">
                        {/* Email */}
                        <div>
                            <label htmlFor="email" className="block text-sm font-semibold text-gray-700 mb-1.5">
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="seu@email.com"
                                autoComplete="email"
                                required
                                aria-describedby={errors.email ? 'email-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${errors.email ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {errors.email && (
                                <p id="email-error" role="alert" className="mt-1.5 text-xs text-red-600">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        {/* Password */}
                        <div>
                            <div className="flex items-center justify-between mb-1.5">
                                <label htmlFor="password" className="block text-sm font-semibold text-gray-700">
                                    Senha
                                </label>
                                <a href="#" className="text-xs text-violet-600 hover:text-violet-800 transition-colors">
                                    Esqueceu a senha?
                                </a>
                            </div>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                                autoComplete="current-password"
                                required
                                aria-describedby={errors.password ? 'password-error' : undefined}
                                className={`w-full rounded-xl border px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all
                                    ${errors.password ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50'}`}
                            />
                            {errors.password && (
                                <p id="password-error" role="alert" className="mt-1.5 text-xs text-red-600">
                                    {errors.password}
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
                                className="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                            />
                            <label htmlFor="remember" className="text-sm text-gray-600">
                                Lembrar de mim
                            </label>
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
                            Entrar
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-gray-500">
                        Não tem uma conta?{' '}
                        <Link href="/register" className="font-semibold text-violet-600 hover:text-violet-800 transition-colors">
                            Criar conta grátis
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
