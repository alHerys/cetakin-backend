<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopPricing;
use App\Models\ShopService;
use App\Models\User;

class ProfileService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function getShop(User $user): Shop
    {
        return $user->shop()->with(['service', 'pricing'])->firstOrFail();
    }

    public function updateShop(User $user, array $data, mixed $photo = null): Shop
    {
        $shop = $user->shop()->firstOrFail();

        if ($photo) {
            $data['shop_photo_url'] = $this->cloudinary->upload($photo, 'shops');
        }

        unset($data['shop_photo']);
        $shop->update($data);

        return $shop->load(['service', 'pricing']);
    }

    public function updateServices(Shop $shop, array $data): ShopService
    {
        return ShopService::updateOrCreate(
            ['shop_id' => $shop->id],
            $data
        );
    }

    public function updatePricing(Shop $shop, array $data): ShopPricing
    {
        return ShopPricing::updateOrCreate(
            ['shop_id' => $shop->id],
            $data
        );
    }
}
