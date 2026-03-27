import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Spinner from '@/Components/Shared/Spinner';
import Logo from '@/Components/Shared/Logo';

interface ResetPasswordProps {
    token: string;
    email: string;
}

interface ResetPasswordForm {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        post('/reset-password', {
            onSuccess: () => toast.success('Senha redefinida com sucesso.'),
            onError: () => toast.error('Nao foi possivel redefinir a senha.'),
            onFinish: () => reset('password', 'password_confirmation'),
        });
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100 px-4 py-12">
            <div className="w-full max-w-md">
                <div className="mb-8 text-center">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <Logo />
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-warm-700">Definir nova senha</h1>
                    <p className="mt-1 text-sm text-warm-500">Escolha uma nova senha para recuperar o acesso.</p>
                </div>

                <div className="rounded-3xl border border-warm-200 bg-white p-8 shadow-xl">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div>
                            <label htmlFor="email" className="mb-1.5 block text-sm font-semibold text-warm-600">
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className={`w-full rounded-xl border px-4 py-3 text-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500 ${
                                    errors.email ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'
                                }`}
                                autoComplete="email"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-xs text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-1.5 block text-sm font-semibold text-warm-600">
                                Nova senha
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className={`w-full rounded-xl border px-4 py-3 text-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500 ${
                                    errors.password ? 'border-red-300 bg-red-50' : 'border-warm-200 bg-warm-50'
                                }`}
                                autoComplete="new-password"
                            />
                            {errors.password && (
                                <p className="mt-1.5 text-xs text-red-600">{errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="mb-1.5 block text-sm font-semibold text-warm-600">
                                Confirmar nova senha
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(event) => setData('password_confirmation', event.target.value)}
                                className="w-full rounded-xl border border-warm-200 bg-warm-50 px-4 py-3 text-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-kintsugi-500"
                                autoComplete="new-password"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-kintsugi-500 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing && <Spinner />}
                            Redefinir senha
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-warm-500">
                        <Link href="/login" className="font-semibold text-kintsugi-600 transition-colors hover:text-kintsugi-700">
                            Voltar ao login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
