<?php

use App\Models\AtkOrder;
use App\Models\PrintOrder;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Submit print review ---

test('user can review a completed print order', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/print/{$order->id}/review", [
        'rating'  => 5,
        'comment' => 'Great!',
    ]);

    $res->assertStatus(201)->assertJsonPath('status', 'success');
});

test('user cannot review a non-completed print order', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->create(['user_id' => $user->id, 'shop_id' => $shop->id, 'status' => 'confirmed']);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/print/{$order->id}/review", [
        'rating' => 5,
    ]);

    $res->assertStatus(422);
});

test('user cannot submit duplicate print review', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    Review::create([
        'user_id'        => $user->id,
        'shop_id'        => $shop->id,
        'print_order_id' => $order->id,
        'order_type'     => 'print',
        'rating'         => 4,
    ]);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/print/{$order->id}/review", [
        'rating' => 5,
    ]);

    $res->assertStatus(422);
});

// --- Submit ATK review ---

test('user can review a completed ATK order', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = AtkOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/atk/{$order->id}/review", [
        'rating'  => 4,
        'comment' => 'Good service.',
    ]);

    $res->assertStatus(201)->assertJsonPath('status', 'success');
});

// --- Shop rating trigger ---

test('shop average rating updates after review', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = AtkOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $this->actingAs($user, 'api')->postJson("/api/v1/orders/atk/{$order->id}/review", [
        'rating' => 5,
    ]);

    expect((float) $shop->fresh()->average_rating)->toBe(5.0)
        ->and($shop->fresh()->total_reviews)->toBe(1);
});

// --- My reviews ---

test('user can list their reviews', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = AtkOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    Review::create([
        'user_id'      => $user->id,
        'shop_id'      => $shop->id,
        'atk_order_id' => $order->id,
        'order_type'   => 'atk',
        'rating'       => 4,
    ]);

    $res = $this->actingAs($user, 'api')->getJson('/api/v1/reviews/me');

    $res->assertStatus(200)->assertJsonCount(1, 'data');
});

test('user can get a specific review', function () {
    $user   = User::factory()->create();
    $shop   = Shop::factory()->approved()->create();
    $order  = AtkOrder::factory()->completed()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);
    $review = Review::create([
        'user_id'      => $user->id,
        'shop_id'      => $shop->id,
        'atk_order_id' => $order->id,
        'order_type'   => 'atk',
        'rating'       => 4,
    ]);

    $res = $this->actingAs($user, 'api')->getJson("/api/v1/reviews/me/{$review->id}");

    $res->assertStatus(200)->assertJsonPath('data.id', $review->id);
});
