<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateAtkOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id'           => ['required', 'uuid', 'exists:shops,id'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.atk_id'   => ['required', 'uuid', 'exists:atk_products,id'],
            'items.*.quantity'  => ['required', 'integer', 'min:1'],
            'notes'             => ['sometimes', 'nullable', 'string'],
        ];
    }
}
