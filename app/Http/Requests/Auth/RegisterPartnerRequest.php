<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPartnerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'shop_name'        => ['required', 'string', 'max:255'],
            'shop_address'     => ['required', 'string'],
            'shop_phone'       => ['nullable', 'string', 'max:20'],
            'shop_description' => ['nullable', 'string'],
            'shop_photo'       => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'open_time'        => ['required', 'date_format:H:i'],
            'close_time'       => ['required', 'date_format:H:i'],
            'operating_days'   => ['required', 'array', 'min:1'],
            'operating_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
