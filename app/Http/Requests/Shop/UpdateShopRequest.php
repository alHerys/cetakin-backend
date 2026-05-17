<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_name'        => ['sometimes', 'string', 'max:255'],
            'shop_address'     => ['sometimes', 'string'],
            'shop_phone'       => ['sometimes', 'nullable', 'string', 'max:20'],
            'shop_description' => ['sometimes', 'nullable', 'string'],
            'shop_photo'       => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'open_time'        => ['sometimes', 'date_format:H:i'],
            'close_time'       => ['sometimes', 'date_format:H:i'],
            'operating_days'   => ['sometimes', 'array', 'min:1'],
            'operating_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'latitude'         => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
