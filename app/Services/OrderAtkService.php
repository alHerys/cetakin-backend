<?php

namespace App\Services;

use App\Models\AtkOrder;
use App\Models\AtkProduct;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderAtkService
{
    const VALID_TRANSITIONS = [
        'pending'          => 'confirmed',
        'confirmed'        => 'processing',
        'processing'       => 'ready_for_pickup',
        'ready_for_pickup' => 'completed',
    ];

    public function createOrder(User $user, array $data): AtkOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $shopId = $data['shop_id'];

            $products = $this->validateAndLoadProducts($shopId, $data['items']);

            $finalPrice = 0;
            $itemRows   = [];

            foreach ($data['items'] as $item) {
                $product  = $products[$item['atk_id']];
                $subtotal = $product->price * $item['quantity'];

                $itemRows[] = [
                    'atk_product_id' => $product->id,
                    'name'           => $product->name,
                    'unit_price'     => $product->price,
                    'quantity'       => $item['quantity'],
                    'subtotal'       => $subtotal,
                ];

                $finalPrice += $subtotal;

                $product->decrement('stock', $item['quantity']);
            }

            $order = AtkOrder::create([
                'user_id'     => $user->id,
                'shop_id'     => $shopId,
                'final_price' => $finalPrice,
                'notes'       => $data['notes'] ?? null,
                'status'      => 'pending',
            ]);

            $order->items()->createMany($itemRows);

            return $order->load(['items', 'shop']);
        });
    }

    public function listOrders(User $user, ?string $status): LengthAwarePaginator
    {
        return AtkOrder::where('user_id', $user->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['shop', 'items.product'])
            ->latest()
            ->paginate(15);
    }

    public function getOrder(User $user, string $id): AtkOrder
    {
        return AtkOrder::where('user_id', $user->id)
            ->with(['shop', 'items', 'statusHistory', 'review'])
            ->findOrFail($id);
    }

    public function updateStatus(Shop $shop, string $orderId, string $newStatus): AtkOrder
    {
        $order = AtkOrder::where('shop_id', $shop->id)->findOrFail($orderId);

        $expected = self::VALID_TRANSITIONS[$order->status] ?? null;

        if ($expected !== $newStatus) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from '{$order->status}' to '{$newStatus}'."],
            ]);
        }

        $order->update(['status' => $newStatus]);

        return $order->load(['user', 'items.product']);
    }

    private function validateAndLoadProducts(string $shopId, array $items): array
    {
        $ids      = array_column($items, 'atk_id');
        $products = AtkProduct::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['atk_id']);

            if (!$product || $product->shop_id !== $shopId) {
                throw ValidationException::withMessages([
                    'items' => ['Product does not belong to this shop.'],
                ]);
            }

            if (!$product->is_available || $product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for \"{$product->name}\"."],
                ]);
            }
        }

        return $products->all();
    }
}
