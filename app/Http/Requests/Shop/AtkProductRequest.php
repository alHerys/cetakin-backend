<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AtkProductRequest extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name'         => [$required, 'string', 'max:255'],
            'description'  => ['sometimes', 'nullable', 'string'],
            'price'        => [$required, 'integer', 'min:0'],
            'stock'        => [$required, 'integer', 'min:0'],
            'photo'        => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
