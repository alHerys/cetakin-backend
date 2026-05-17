<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrintOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id'     => ['required', 'uuid', 'exists:shops,id'],
            'file'        => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'paper_size'  => ['required', 'string', 'in:A4,A3,F4'],
            'color_mode'  => ['required', 'string', 'in:black_and_white,full_color'],
            'sides'       => ['required', 'string', 'in:single,double'],
            'binding'     => ['required', 'string', 'in:none,staple,spiral'],
            'copies'      => ['required', 'integer', 'min:1'],
            'total_pages' => ['required', 'integer', 'min:1'],
            'notes'       => ['sometimes', 'nullable', 'string'],
        ];
    }
}
