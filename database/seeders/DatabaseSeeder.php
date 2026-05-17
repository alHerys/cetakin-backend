<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@printapp.com'],
            [
                'name'     => 'Super Admin',
                'password' => 'admin123',
                'role'     => 'admin',
            ]
        );
    }
}
