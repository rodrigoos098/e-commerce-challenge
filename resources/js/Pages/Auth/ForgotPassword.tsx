import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Spinner from '@/Components/Shared/Spinner';
import Logo from '@/Components/Shared/Logo';

interface ForgotPasswordForm {
    email: string;
}

export default function ForgotPassword() {
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({
        email: '',
    });

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        post('/forgot-password', {
            onSuccess: () => toast.success('Se o e-mail existir, o link de redefinicao foi enviado.'),
            onError: () => toast.error('Nao foi possivel enviar o link de redefinicao.'),
        });
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100 px-4 py-12">
            <div className="w-full max-w-md">
                <div className="mb-8 text-center">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <Logo />
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-warm-700">Recuperar senha</h1>
                    <p className="mt-1 text-sm text-warm-500">Informe seu e-mail para receber o link de redefinicao.</p>
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
                                placeholder="seu@email.com"
                                autoComplete="email"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-xs text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-kintsugi-500 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing && <Spinner />}
                            Enviar link de redefinicao
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-warm-500">
                        Lembrou a senha?{' '}
                        <Link href="/login" className="font-semibold text-kintsugi-600 transition-colors hover:text-kintsugi-700">
                            Voltar ao login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
