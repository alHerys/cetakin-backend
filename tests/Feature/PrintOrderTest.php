<?php

use App\Models\PrintOrder;
use App\Models\Shop;
use App\Models\ShopPricing;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function makePrintOrderSetup(): array
{
    $user    = User::factory()->create();
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);
    ShopPricing::factory()->create([
        'shop_id'                  => $shop->id,
        'black_and_white_per_page' => 500,
        'full_color_per_page'      => 1500,
        'double_side_surcharge'    => 200,
        'binding_prices'           => ['none' => 0, 'staple' => 2000, 'spiral' => 5000],
    ]);

    return [$user, $partner, $shop];
}

// --- Place order ---

test('user can place a print order', function () {
    [$user, $partner, $shop] = makePrintOrderSetup();

    $this->mock(CloudinaryService::class)
        ->shouldReceive('upload')
        ->once()
        ->andReturn('https://res.cloudinary.com/fake/test.pdf');

    $res = $this->actingAs($user, 'api')->postJson('/api/v1/orders/print', [
        'shop_id'     => $shop->id,
        'file'        => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        'paper_size'  => 'A4',
        'color_mode'  => 'black_and_white',
        'sides'       => 'single',
        'binding'     => 'none',
        'copies'      => 2,
        'total_pages' => 10,
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.final_price', 10000);
});

test('place order fails without pricing', function () {
    $user    = User::factory()->create();
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);

    $res = $this->actingAs($user, 'api')->postJson('/api/v1/orders/print', [
        'shop_id'     => $shop->id,
        'file'        => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        'paper_size'  => 'A4',
        'color_mode'  => 'black_and_white',
        'sides'       => 'single',
        'binding'     => 'none',
        'copies'      => 1,
        'total_pages' => 5,
    ]);

    $res->assertStatus(422);
});

test('partner cannot place a print order', function () {
    [$user, $partner, $shop] = makePrintOrderSetup();

    $res = $this->actingAs($partner, 'api')->postJson('/api/v1/orders/print', []);

    $res->assertStatus(403);
});

// --- List orders ---

test('user can list their print orders', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    PrintOrder::factory()->count(3)->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->getJson('/api/v1/orders/print');

    $res->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('user only sees their own orders', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    PrintOrder::factory()->count(2)->create(['user_id' => $user1->id, 'shop_id' => $shop->id]);
    PrintOrder::factory()->count(3)->create(['user_id' => $user2->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user1, 'api')->getJson('/api/v1/orders/print');

    $res->assertStatus(200)->assertJsonCount(2, 'data');
});

// --- Show order ---

test('user can view their print order detail', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->create(['user_id' => $user->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user, 'api')->getJson("/api/v1/orders/print/{$order->id}");

    $res->assertStatus(200)
        ->assertJsonPath('data.id', $order->id);
});

test('user cannot view another user\'s order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->create(['user_id' => $user2->id, 'shop_id' => $shop->id]);

    $res = $this->actingAs($user1, 'api')->getJson("/api/v1/orders/print/{$order->id}");

    $res->assertStatus(404);
});

// --- Cancel order ---

test('user can cancel a pending order', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->create(['user_id' => $user->id, 'shop_id' => $shop->id, 'status' => 'pending']);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/print/{$order->id}/cancel");

    $res->assertStatus(200);
    expect($order->fresh()->status)->toBe('cancelled');
});

test('user cannot cancel a confirmed order', function () {
    $user  = User::factory()->create();
    $shop  = Shop::factory()->approved()->create();
    $order = PrintOrder::factory()->create(['user_id' => $user->id, 'shop_id' => $shop->id, 'status' => 'confirmed']);

    $res = $this->actingAs($user, 'api')->postJson("/api/v1/orders/print/{$order->id}/cancel");

    $res->assertStatus(422);
});

// --- Partner status update ---

test('partner can advance order status', function () {
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);
    $order   = PrintOrder::factory()->create(['shop_id' => $shop->id, 'status' => 'pending']);

    $res = $this->actingAs($partner, 'api')
        ->patchJson("/api/v1/partner/orders/print/{$order->id}/status", ['status' => 'confirmed']);

    $res->assertStatus(200);
    expect($order->fresh()->status)->toBe('confirmed');
});

test('partner cannot skip status transitions', function () {
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);
    $order   = PrintOrder::factory()->create(['shop_id' => $shop->id, 'status' => 'pending']);

    $res = $this->actingAs($partner, 'api')
        ->patchJson("/api/v1/partner/orders/print/{$order->id}/status", ['status' => 'completed']);

    $res->assertStatus(422);
});

test('partner cannot update another shop\'s order', function () {
    $partner1 = User::factory()->partner()->create();
    $partner2 = User::factory()->partner()->create();
    $shop1    = Shop::factory()->approved()->create(['user_id' => $partner1->id]);
    $shop2    = Shop::factory()->approved()->create(['user_id' => $partner2->id]);
    $order    = PrintOrder::factory()->create(['shop_id' => $shop2->id, 'status' => 'pending']);

    $res = $this->actingAs($partner1, 'api')
        ->patchJson("/api/v1/partner/orders/print/{$order->id}/status", ['status' => 'confirmed']);

    $res->assertStatus(404);
});
