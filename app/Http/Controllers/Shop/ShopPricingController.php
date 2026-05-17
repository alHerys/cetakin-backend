<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopPricingRequest;
use App\Services\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ShopPricingController extends Controller
{
    use ApiResponse;

    public function __construct(private ProfileService $profileService) {}

    public function update(UpdateShopPricingRequest $request): JsonResponse
    {
        $shop = auth()->user()->shop;
        $pricing = $this->profileService->updatePricing($shop, $request->validated());

        return $this->success($pricing, 'Shop pricing updated successfully.');
    }
}
