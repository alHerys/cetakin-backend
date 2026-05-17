<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopPricingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'black_and_white_per_page' => ['required', 'integer', 'min:0'],
            'full_color_per_page'      => ['required', 'integer', 'min:0'],
            'double_side_surcharge'    => ['required', 'integer', 'min:0'],
            'binding_prices'           => ['required', 'array'],
            'binding_prices.none'      => ['sometimes', 'integer', 'min:0'],
            'binding_prices.staple'    => ['sometimes', 'integer', 'min:0'],
            'binding_prices.spiral'    => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
