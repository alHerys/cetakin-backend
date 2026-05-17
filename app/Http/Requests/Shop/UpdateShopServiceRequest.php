<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'paper_sizes'   => ['required', 'array'],
            'paper_sizes.*' => ['string', 'in:A4,A3,F4'],
            'color_modes'   => ['required', 'array'],
            'color_modes.*' => ['string', 'in:black_and_white,full_color'],
            'sides'         => ['required', 'array'],
            'sides.*'       => ['string', 'in:single,double'],
            'bindings'      => ['required', 'array'],
            'bindings.*'    => ['string', 'in:none,staple,spiral'],
        ];
    }
}
