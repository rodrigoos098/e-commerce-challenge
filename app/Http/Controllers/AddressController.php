<?php

namespace App\Http\Controllers;

use App\Http\Requests\Web\StoreAddressRequest;
use App\Http\Requests\Web\UpdateAddressRequest;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Address::class);

        return Inertia::render('Customer/Addresses/Index', [
            'addresses' => $this->serializeAddresses($request->user()->addresses()
                ->orderByDesc('is_default_shipping')
                ->orderByDesc('is_default_billing')
                ->latest()
                ->get()),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $this->authorize('create', Address::class);

        $address = $request->user()->addresses()->create($request->validated());

        $this->syncDefaults($request->user(), $address);

        return back()->with('success', 'Endereco salvo com sucesso!');
    }

    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $address->update($request->validated());

        $this->syncDefaults($request->user(), $address);

        return back()->with('success', 'Endereco atualizado com sucesso!');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $wasDefaultShipping = $address->is_default_shipping;
        $wasDefaultBilling = $address->is_default_billing;
        $address->delete();

        if ($wasDefaultShipping) {
            $this->ensureDefaultExists($request->user(), 'is_default_shipping');
        }

        if ($wasDefaultBilling) {
            $this->ensureDefaultExists($request->user(), 'is_default_billing');
        }

        return back()->with('success', 'Endereco removido com sucesso!');
    }

    public function setDefaultShipping(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $address->forceFill([
            'is_default_shipping' => true,
        ])->save();

        $request->user()->addresses()->whereKeyNot($address->id)->update([
            'is_default_shipping' => false,
        ]);

        return back()->with('success', 'Endereco padrao de entrega atualizado!');
    }

    public function setDefaultBilling(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $address->forceFill([
            'is_default_billing' => true,
        ])->save();

        $request->user()->addresses()->whereKeyNot($address->id)->update([
            'is_default_billing' => false,
        ]);

        return back()->with('success', 'Endereco padrao de cobranca atualizado!');
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zip_code' => ['required', 'string', 'max:20'],
        ]);

        $zipCode = preg_replace('/\D+/', '', $validated['zip_code']) ?? '';

        if (strlen($zipCode) !== 8) {
            return response()->json([
                'found' => false,
            ], 422);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get("https://viacep.com.br/ws/{$zipCode}/json/");
        } catch (ConnectionException) {
            return response()->json([
                'found' => false,
            ]);
        }

        if (! $response->ok()) {
            return response()->json([
                'found' => false,
            ]);
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['erro'] ?? false) === true) {
            return response()->json([
                'found' => false,
            ]);
        }

        return response()->json([
            'found' => true,
            'street' => trim((string) ($payload['logradouro'] ?? '')),
            'city' => trim((string) ($payload['localidade'] ?? '')),
            'state' => trim((string) ($payload['uf'] ?? '')),
        ]);
    }

    /**
     * @param  Collection<int, Address>  $addresses
     * @return array<int, array<string, mixed>>
     */
    private function serializeAddresses(Collection $addresses): array
    {
        return $addresses->map(fn (Address $address): array => [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'street' => $address->street,
            'city' => $address->city,
            'state' => $address->state,
            'zip_code' => $address->zip_code,
            'country' => $address->country,
            'is_default_shipping' => $address->is_default_shipping,
            'is_default_billing' => $address->is_default_billing,
        ])->values()->all();
    }

    private function syncDefaults(User $user, Address $address): void
    {
        if ($address->is_default_shipping) {
            $user->addresses()->whereKeyNot($address->id)->update([
                'is_default_shipping' => false,
            ]);
        }

        if ($address->is_default_billing) {
            $user->addresses()->whereKeyNot($address->id)->update([
                'is_default_billing' => false,
            ]);
        }

        $this->ensureDefaultExists(
            $user,
            'is_default_shipping',
            $address->is_default_shipping ? $address->id : $user->addresses()->whereKeyNot($address->id)->latest('id')->value('id'),
        );

        $this->ensureDefaultExists(
            $user,
            'is_default_billing',
            $address->is_default_billing ? $address->id : $user->addresses()->whereKeyNot($address->id)->latest('id')->value('id'),
        );
    }

    private function ensureDefaultExists(User $user, string $column, ?int $preferredAddressId = null): void
    {
        if (! $user->addresses()->exists() || $user->addresses()->where($column, true)->exists()) {
            return;
        }

        $fallback = $preferredAddressId !== null
            ? $user->addresses()->find($preferredAddressId)
            : $user->addresses()->latest('id')->first();

        $fallback ??= $user->addresses()->latest('id')->first();

        $fallback?->forceFill([
            $column => true,
        ])->save();
    }
}
