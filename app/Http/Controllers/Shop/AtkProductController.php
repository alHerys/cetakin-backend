<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AtkProductRequest;
use App\Models\AtkProduct;
use App\Services\SellingProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AtkProductController extends Controller
{
    use ApiResponse;

    public function __construct(private SellingProductService $sellingProductService) {}

    public function index(): JsonResponse
    {
        $shop = auth()->user()->shop;
        $products = $this->sellingProductService->listProducts($shop);

        return $this->paginated($products, 'Products retrieved successfully.');
    }

    public function store(AtkProductRequest $request): JsonResponse
    {
        $shop = auth()->user()->shop;
        $product = $this->sellingProductService->addProduct(
            $shop,
            $request->validated(),
            $request->file('photo')
        );

        return $this->success($product, 'Product created successfully.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = auth()->user()->shop->atkProducts()->findOrFail($id);

        return $this->success($product, 'Product retrieved successfully.');
    }

    public function update(AtkProductRequest $request, string $id): JsonResponse
    {
        $product = auth()->user()->shop->atkProducts()->findOrFail($id);
        $product = $this->sellingProductService->updateProduct(
            $product,
            $request->validated(),
            $request->file('photo')
        );

        return $this->success($product, 'Product updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $product = auth()->user()->shop->atkProducts()->findOrFail($id);
        $this->sellingProductService->deleteProduct($product);

        return $this->success(null, 'Product deleted successfully.');
    }
}
