<?php

namespace App\Http\Requests\Web;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipping_mode' => ['required', Rule::in(['saved', 'new'])],
            'shipping_address_id' => [
                Rule::requiredIf(fn (): bool => $this->shippingUsesSavedAddress()),
                'nullable',
                'integer',
                Rule::exists(Address::class, 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'shipping_name' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:255'],
            'shipping_street' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:255'],
            'shipping_city' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:255'],
            'shipping_state' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:255'],
            'shipping_zip' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:20'],
            'shipping_country' => [Rule::requiredIf(fn (): bool => ! $this->shippingUsesSavedAddress()), 'nullable', 'string', 'max:255'],
            'same_billing' => ['required', 'boolean'],
            'billing_mode' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling()), 'nullable', Rule::in(['saved', 'new'])],
            'billing_address_id' => [
                Rule::requiredIf(fn (): bool => $this->billingUsesSavedAddress()),
                'nullable',
                'integer',
                Rule::exists(Address::class, 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'billing_name' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:255'],
            'billing_street' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:255'],
            'billing_city' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:255'],
            'billing_state' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:255'],
            'billing_zip' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:20'],
            'billing_country' => [Rule::requiredIf(fn (): bool => ! $this->usesSameBilling() && $this->input('billing_mode') === 'new'), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_simulated' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'Selecione um endereco de entrega salvo.',
            'billing_mode.required' => 'Informe como deseja preencher o endereco de cobranca.',
            'billing_address_id.required' => 'Selecione um endereco de cobranca salvo.',
            'shipping_name.required' => 'Informe o nome para entrega.',
            'shipping_street.required' => 'Informe a rua de entrega.',
            'shipping_city.required' => 'Informe a cidade de entrega.',
            'shipping_state.required' => 'Informe o estado de entrega.',
            'shipping_zip.required' => 'Informe o CEP de entrega.',
            'shipping_country.required' => 'Informe o pais de entrega.',
            'billing_name.required' => 'Informe o nome para cobranca.',
            'billing_street.required' => 'Informe a rua de cobranca.',
            'billing_city.required' => 'Informe a cidade de cobranca.',
            'billing_state.required' => 'Informe o estado de cobranca.',
            'billing_zip.required' => 'Informe o CEP de cobranca.',
            'billing_country.required' => 'Informe o pais de cobranca.',
            'same_billing.required' => 'Informe se o endereco de cobranca e o mesmo da entrega.',
            'notes.string' => 'As observacoes do pedido devem ser um texto.',
            'payment_simulated.accepted' => 'Simule o pagamento antes de concluir o pedido.',
        ];
    }

    /**
     * @return array{name: string, street: string, city: string, state: string, zip_code: string, country: string}
     */
    public function shippingAddressSnapshot(): array
    {
        if ($this->shippingUsesSavedAddress()) {
            return $this->resolveSavedAddress((int) $this->integer('shipping_address_id'))->toOrderSnapshot();
        }

        return $this->manualAddressSnapshot('shipping');
    }

    /**
     * @return array{name: string, street: string, city: string, state: string, zip_code: string, country: string}
     */
    public function billingAddressSnapshot(): array
    {
        if ($this->usesSameBilling()) {
            return $this->shippingAddressSnapshot();
        }

        if ($this->billingUsesSavedAddress()) {
            return $this->resolveSavedAddress((int) $this->integer('billing_address_id'))->toOrderSnapshot();
        }

        return $this->manualAddressSnapshot('billing');
    }

    private function shippingUsesSavedAddress(): bool
    {
        return $this->input('shipping_mode') === 'saved';
    }

    private function usesSameBilling(): bool
    {
        return $this->boolean('same_billing');
    }

    private function billingUsesSavedAddress(): bool
    {
        return ! $this->usesSameBilling() && $this->input('billing_mode') === 'saved';
    }
    /**
     * @return array{name: string, street: string, city: string, state: string, zip_code: string, country: string}
     */
    private function manualAddressSnapshot(string $prefix): array
    {
        return [
            'name' => (string) $this->input("{$prefix}_name"),
            'street' => (string) $this->input("{$prefix}_street"),
            'city' => (string) $this->input("{$prefix}_city"),
            'state' => (string) $this->input("{$prefix}_state"),
            'zip_code' => (string) $this->input("{$prefix}_zip"),
            'country' => (string) $this->input("{$prefix}_country"),
        ];
    }

    private function resolveSavedAddress(int $addressId): Address
    {
        return $this->user()->addresses()->findOrFail($addressId);
    }
}
