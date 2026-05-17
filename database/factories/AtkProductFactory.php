<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtkProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id'      => Shop::factory()->approved(),
            'name'         => fake()->word() . ' ' . fake()->word(),
            'description'  => fake()->sentence(),
            'price'        => fake()->numberBetween(1000, 20000),
            'stock'        => 50,
            'is_available' => true,
        ];
    }
}
