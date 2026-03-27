import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Spinner from '@/Components/Shared/Spinner';
import Logo from '@/Components/Shared/Logo';

export default function VerifyEmail() {
    const { post, processing } = useForm({});

    function handleResend(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        post('/email/verification-notification', {
            onSuccess: () => toast.success('Novo link de verificacao enviado.'),
            onError: () => toast.error('Nao foi possivel reenviar o link.'),
        });
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-kintsugi-50 via-cream to-warm-100 px-4 py-12">
            <div className="w-full max-w-md">
                <div className="mb-8 text-center">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <Logo />
                    </Link>
                    <h1 className="mt-6 text-2xl font-bold text-warm-700">Confirme seu e-mail</h1>
                    <p className="mt-1 text-sm text-warm-500">
                        Antes de finalizar compras, precisamos confirmar que este e-mail pertence a voce.
                    </p>
                </div>

                <div className="rounded-3xl border border-warm-200 bg-white p-8 shadow-xl">
                    <form onSubmit={handleResend} className="space-y-5">
                        <div className="rounded-2xl border border-kintsugi-100 bg-kintsugi-50 px-4 py-4 text-sm text-warm-600">
                            Verifique sua caixa de entrada e clique no link enviado no cadastro. Se nao encontrar, solicite um novo envio abaixo.
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-kintsugi-500 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-kintsugi-600 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing && <Spinner />}
                            Reenviar e-mail de verificacao
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-warm-500">
                        <Link href="/" className="font-semibold text-kintsugi-600 transition-colors hover:text-kintsugi-700">
                            Voltar para a loja
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
