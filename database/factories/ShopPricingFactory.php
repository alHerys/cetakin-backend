<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopPricingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id'                  => Shop::factory()->approved(),
            'black_and_white_per_page' => 500,
            'full_color_per_page'      => 1500,
            'double_side_surcharge'    => 200,
            'binding_prices'           => ['none' => 0, 'staple' => 2000, 'spiral' => 5000],
        ];
    }
}
