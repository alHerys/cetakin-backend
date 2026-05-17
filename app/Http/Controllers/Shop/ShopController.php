<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Services\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ShopController extends Controller
{
    use ApiResponse;

    public function __construct(private ProfileService $profileService) {}

    public function show(): JsonResponse
    {
        $shop = $this->profileService->getShop(auth()->user());

        return $this->success($shop, 'Shop retrieved successfully.');
    }

    public function update(UpdateShopRequest $request): JsonResponse
    {
        $shop = $this->profileService->updateShop(
            auth()->user(),
            $request->validated(),
            $request->file('shop_photo')
        );

        return $this->success($shop, 'Shop updated successfully.');
    }
}
