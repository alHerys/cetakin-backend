<?php

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAdmin(): User
{
    return User::factory()->admin()->create();
}

function makePendingPartnerShop(): array
{
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->create(['user_id' => $partner->id, 'status' => 'pending']);
    return [$partner, $shop];
}

// --- List partners ---

test('admin can list partners', function () {
    makePendingPartnerShop();
    $admin = makeAdmin();

    $res = $this->actingAs($admin, 'api')->getJson('/api/v1/admin/partners');

    $res->assertStatus(200)->assertJsonPath('status', 'success');
});

test('non-admin cannot list partners', function () {
    $user = User::factory()->create();

    $res = $this->actingAs($user, 'api')->getJson('/api/v1/admin/partners');

    $res->assertStatus(403);
});

test('unauthenticated cannot list partners', function () {
    $res = $this->getJson('/api/v1/admin/partners');

    $res->assertStatus(401);
});

// --- Approve (route uses shop ID) ---

test('admin can approve a pending partner', function () {
    [$partner, $shop] = makePendingPartnerShop();
    $admin = makeAdmin();

    $res = $this->actingAs($admin, 'api')
        ->patchJson("/api/v1/admin/partners/{$shop->id}/approve");

    $res->assertStatus(200);
    expect($shop->fresh()->status)->toBe('approved');
});

test('admin cannot approve an already approved partner', function () {
    $partner = User::factory()->partner()->create();
    $shop    = Shop::factory()->approved()->create(['user_id' => $partner->id]);
    $admin   = makeAdmin();

    $res = $this->actingAs($admin, 'api')
        ->patchJson("/api/v1/admin/partners/{$shop->id}/approve");

    $res->assertStatus(422);
});

// --- Reject ---

test('admin can reject a pending partner with a reason', function () {
    [$partner, $shop] = makePendingPartnerShop();
    $admin = makeAdmin();

    $res = $this->actingAs($admin, 'api')
        ->patchJson("/api/v1/admin/partners/{$shop->id}/reject", [
            'reason' => 'Incomplete documents.',
        ]);

    $res->assertStatus(200);
    expect($shop->fresh()->status)->toBe('rejected');
});

test('reject requires a reason', function () {
    [$partner, $shop] = makePendingPartnerShop();
    $admin = makeAdmin();

    $res = $this->actingAs($admin, 'api')
        ->patchJson("/api/v1/admin/partners/{$shop->id}/reject", []);

    $res->assertStatus(422);
});
