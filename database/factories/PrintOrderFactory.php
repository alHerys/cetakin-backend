<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrintOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'shop_id'     => Shop::factory()->approved(),
            'file_url'    => 'https://example.com/test.pdf',
            'paper_size'  => 'A4',
            'color_mode'  => 'black_and_white',
            'sides'       => 'single',
            'binding'     => 'none',
            'copies'      => 1,
            'total_pages' => 5,
            'final_price' => 2500,
            'status'      => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
