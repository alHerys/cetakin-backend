<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Register user ---

test('user can register', function () {
    $res = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'user@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.user.role', 'user');
});

test('register fails with duplicate email', function () {
    User::factory()->create(['email' => 'user@test.com']);

    $res = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'user@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $res->assertStatus(422);
});

// --- Register partner ---

test('partner can register', function () {
    $res = $this->postJson('/api/v1/auth/register/partner', [
        'name'                  => 'Partner',
        'email'                 => 'partner@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'shop_name'             => 'Print Shop',
        'shop_address'          => 'Jl. Sudirman No. 1',
        'open_time'             => '08:00',
        'close_time'            => '17:00',
        'operating_days'        => ['monday', 'tuesday'],
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.user.role', 'partner');
});

// --- Login ---

test('user can login', function () {
    User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('password123')]);

    $res = $this->postJson('/api/v1/auth/login', [
        'email'    => 'user@test.com',
        'password' => 'password123',
    ]);

    $res->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['token']]);
});

test('login fails with wrong password', function () {
    User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('password123')]);

    $res = $this->postJson('/api/v1/auth/login', [
        'email'    => 'user@test.com',
        'password' => 'wrongpassword',
    ]);

    $res->assertStatus(422);
});

// --- Me ---

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $res = $this->actingAs($user, 'api')
        ->getJson('/api/v1/auth/me');

    $res->assertStatus(200)
        ->assertJsonPath('data.id', $user->id);
});

test('unauthenticated request to me returns 401', function () {
    $res = $this->getJson('/api/v1/auth/me');

    $res->assertStatus(401);
});

// --- Logout ---

test('authenticated user can logout', function () {
    $user  = User::factory()->create(['email' => 'u@test.com', 'password' => bcrypt('password123')]);
    $token = $this->postJson('/api/v1/auth/login', ['email' => 'u@test.com', 'password' => 'password123'])
        ->json('data.token');

    $res = $this->withToken($token)->postJson('/api/v1/auth/logout');

    $res->assertStatus(200);
});
