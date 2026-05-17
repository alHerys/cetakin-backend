<?php

namespace App\Services;

use App\Models\AtkProduct;
use App\Models\Shop;
use Illuminate\Pagination\LengthAwarePaginator;

class SellingProductService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function listProducts(Shop $shop): LengthAwarePaginator
    {
        return $shop->atkProducts()->latest()->paginate(15);
    }

    public function addProduct(Shop $shop, array $data, mixed $photo = null): AtkProduct
    {
        if ($photo) {
            $data['photo_url'] = $this->cloudinary->upload($photo, 'atk-products');
        }

        unset($data['photo']);

        return $shop->atkProducts()->create($data);
    }

    public function updateProduct(AtkProduct $product, array $data, mixed $photo = null): AtkProduct
    {
        if ($photo) {
            $data['photo_url'] = $this->cloudinary->upload($photo, 'atk-products');
        }

        unset($data['photo']);
        $product->update($data);

        return $product;
    }

    public function deleteProduct(AtkProduct $product): void
    {
        $product->delete();
    }
}
