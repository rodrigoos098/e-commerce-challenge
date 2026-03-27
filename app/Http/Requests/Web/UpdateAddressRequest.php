<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
            'is_default_shipping' => ['required', 'boolean'],
            'is_default_billing' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'Informe um apelido para o endereço.',
            'recipient_name.required' => 'Informe o nome do destinatário.',
            'street.required' => 'Informe a rua e número.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe o estado.',
            'zip_code.required' => 'Informe o CEP.',
            'country.required' => 'Informe o país.',
        ];
    }
}
