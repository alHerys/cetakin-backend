<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Services\DiscoveryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    use ApiResponse;

    public function __construct(private DiscoveryService $discoveryService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat'        => ['required', 'numeric', 'between:-90,90'],
            'lng'        => ['required', 'numeric', 'between:-180,180'],
            'radius'     => ['sometimes', 'numeric', 'min:1', 'max:100'],
            'min_rating' => ['sometimes', 'numeric', 'between:1,5'],
        ]);

        $shops = $this->discoveryService->searchShops($request->only('lat', 'lng', 'radius', 'min_rating'));

        return $this->paginated($shops, 'Shops retrieved successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $shop = $this->discoveryService->getShop($id);

        return $this->success($shop, 'Shop retrieved successfully.');
    }

    public function atkCatalog(string $id): JsonResponse
    {
        $products = $this->discoveryService->getAtkCatalog($id);

        return $this->paginated($products, 'ATK catalog retrieved successfully.');
    }
}
