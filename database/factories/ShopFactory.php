<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory()->partner(),
            'shop_name'    => fake()->company(),
            'shop_address' => fake()->address(),
            'open_time'    => '08:00:00',
            'close_time'   => '17:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday'],
            'status'       => 'pending',
            'latitude'     => fake()->latitude(-8, -6),
            'longitude'    => fake()->longitude(106, 107),
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}
