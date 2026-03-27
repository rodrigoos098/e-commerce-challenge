<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Order;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Order::STATUSES)],
        ];
    }
}
