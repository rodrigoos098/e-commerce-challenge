<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\UpdatePasswordRequest;
use App\Http\Requests\Web\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfilePageController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Customer/Profile', [
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->authService->updateProfile($request->user(), $request->validated());

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->authService->updatePassword($request->user(), $request->validated()['password']);

        return back()->with('success', 'Senha alterada com sucesso!');
    }
}
