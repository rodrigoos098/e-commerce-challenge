<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\ForgotPasswordRequest;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use App\Http\Requests\Web\ResetPasswordRequest;
use App\Services\AuthService;
use App\Services\CartService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthPageController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly CartService $cartService,
    ) {
    }

    public function loginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function forgotPasswordForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $guestCartId = $request->session()->get('guest_cart_id');
            $this->cartService->mergeSessionCartIntoUser($request->session()->getId(), $user->id, $guestCartId);
            $request->session()->forget('guest_cart_id');
            $request->session()->regenerate();

            if ($user->hasRole('admin')) {
                return redirect()->intended('/admin/dashboard')->with('success', 'Bem-vindo de volta!');
            }

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('success', 'Sua conta ainda precisa ser verificada.');
            }

            return redirect()->intended('/')->with('success', 'Bem-vindo de volta!');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas estão incorretas.',
        ])->onlyInput('email');
    }

    public function registerForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->validated());

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return back()->with('success', 'Enviamos um link de redefinicao para o seu e-mail.');
    }

    public function resetPasswordForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function ($user, string $password): void {
                $this->authService->updatePassword($user, $password);
                $user->forceFill([
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()->route('login')->with('success', 'Senha redefinida com sucesso. Faça login para continuar.');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->authService->registerUser($request->validated());

        Auth::login($user);
        $guestCartId = $request->session()->get('guest_cart_id');
        $this->cartService->mergeSessionCartIntoUser($request->session()->getId(), $user->id, $guestCartId);
        $request->session()->forget('guest_cart_id');
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('success', 'Conta criada com sucesso! Verifique seu e-mail para continuar.');
    }

    public function verificationNotice(): Response|RedirectResponse
    {
        if (request()->user()?->hasVerifiedEmail()) {
            return redirect('/');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect('/');
        }

        $request->user()?->sendEmailVerificationNotification();

        return back()->with('success', 'Enviamos um novo link de verificacao para o seu e-mail.');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect('/')->with('success', 'E-mail verificado com sucesso.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
