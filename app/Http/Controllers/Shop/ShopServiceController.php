<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopServiceRequest;
use App\Services\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ShopServiceController extends Controller
{
    use ApiResponse;

    public function __construct(private ProfileService $profileService) {}

    public function update(UpdateShopServiceRequest $request): JsonResponse
    {
        $shop = auth()->user()->shop;
        $service = $this->profileService->updateServices($shop, $request->validated());

        return $this->success($service, 'Shop services updated successfully.');
    }
}
