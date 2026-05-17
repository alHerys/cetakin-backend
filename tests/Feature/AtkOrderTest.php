<?php

use App\Models\AtkOrder;
use App\Models\AtkOrderItem;
use App\Models\AtkProduct;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAtkSetup(): array
{
    $user    = User::factory()->create();
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);
    $product = AtkProduct::factory()->create(['shop_id' => $shop->id, 'price' => 2000, 'stock' => 50]);

    return [$user, $partner, $shop, $product];
}

// --- Place order ---

test('user can place an ATK order', function () {
    [$user, $partner, $shop, $product] = makeAtkSetup();

    $res = $this->actingAs($user, 'api')->postJson('/api/v1/orders/atk', [
        'shop_id' => $shop->id,
        'items'   => [['atk_id' => $product->id, 'quantity' => 3]],
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.final_price', 6000);

    expect($product->fresh()->stock)->toBe(47);
});

test('order fails when stock is insufficient', function () {
    [$user, $partner, $shop, $product] = makeAtkSetup();

    $res = $this->actingAs($user, 'api')->postJson('/api/v1/orders/atk', [
        'shop_id' => $shop->id,
        'items'   => [['atk_id' => $product->id, 'quantity' => 999]],
    ]);

    $res->assertStatus(422);
    expect($product->fresh()->stock)->toBe(50);
});

test('order fails when product does not belong to shop', function () {
    [$user, $partner, $shop, $product] = makeAtkSetup();
    $otherShop    = Shop::factory()->approved()->create();
    $otherProduct = AtkProduct::factory()->create(['shop_id' => $otherShop->id]);

    $res = $this->actingAs($user, 'api')->postJson('/api/v1/orders/atk', [
        'shop_id' => $shop->id,
        'items'   => [['atk_id' => $otherProduct->id, 'quantity' => 1]],
    ]);

    $res->assertStatus(422);
});

test('order items snapshot name and price at order time', function () {
    [$user, $partner, $shop, $product] = makeAtkSetup();

    $this->actingAs($user, 'api')->postJson('/api/v1/orders/atk', [
        'shop_id' => $shop->id,
        'items'   => [['atk_id' => $product->id, 'quantity' => 1]],
    ]);

    $item = AtkOrderItem::first();
    expect($item->name)->toBe($product->name)
        ->and($item->unit_price)->toBe($product->price);
});

test('partner cannot place an ATK order', function () {
    [$user, $partner, $shop, $product] = makeAtkSetup();

    $res = $this->actingAs($partner, 'api')->postJson('/api/v1/orders/atk', []);

    $res->assertStatus(403);
});

// --- List & show ---

test('user can list their ATK orders', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->approved()->create();
    AtkOrder::factory()->count(3)->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->getJson('/api/v1/orders/atk');

    $res->assertStatus(200)->assertJsonCount(3, 'data');
});

test('user can view their ATK order detail', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = AtkOrder::factory()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->getJson("/api/v1/orders/atk/{$order->id}");

    $res->assertStatus(200)->assertJsonPath('data.id', $order->id);
});

// --- Partner status update ---

test('partner can advance ATK order status', function () {
    [$user, $partner, $shop] = makeAtkSetup();
    $order = AtkOrder::factory()->create(['shop_id' => $shop->id, 'status' => 'pending']);

    $res = $this->actingAs($partner, 'api')
        ->patchJson("/api/v1/partner/orders/atk/{$order->id}/status", ['status' => 'confirmed']);

    $res->assertStatus(200);
    expect($order->fresh()->status)->toBe('confirmed');
});

test('partner cannot skip ATK order status transitions', function () {
    [$user, $partner, $shop] = makeAtkSetup();
    $order = AtkOrder::factory()->create(['shop_id' => $shop->id, 'status' => 'pending']);

    $res = $this->actingAs($partner, 'api')
        ->patchJson("/api/v1/partner/orders/atk/{$order->id}/status", ['status' => 'completed']);

    $res->assertStatus(422);
});
