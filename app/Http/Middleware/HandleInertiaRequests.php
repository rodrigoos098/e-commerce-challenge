<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at?->toISOString(),
                    'email_verified' => $request->user()->hasVerifiedEmail(),
                    'roles' => $request->user()->getRoleNames()->toArray(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'cart_count' => fn () => $request->user()
                ? CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $request->user()->id))->count()
                : Cart::query()->where('session_id', $request->session()->getId())->first()?->items()->count() ?? 0,
        ];
    }
}
