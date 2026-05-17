<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'shop_id'     => Shop::factory()->approved(),
            'final_price' => 6000,
            'status'      => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
