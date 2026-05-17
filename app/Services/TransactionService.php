<?php

namespace App\Services;

use App\Models\AtkOrder;
use App\Models\PrintOrder;
use App\Models\Review;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function getShopReviews(string $shopId): LengthAwarePaginator
    {
        return Review::with('user')
            ->where('shop_id', $shopId)
            ->latest('created_at')
            ->paginate(15);
    }

    public function getUserReviews(User $user): LengthAwarePaginator
    {
        return Review::where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(15);
    }

    public function getUserReview(User $user, string $reviewId): Review
    {
        return Review::where('user_id', $user->id)->findOrFail($reviewId);
    }

    public function submitPrintReview(User $user, string $orderId, array $data): Review
    {
        $order = PrintOrder::where('user_id', $user->id)->findOrFail($orderId);

        if ($order->status !== 'completed') {
            throw ValidationException::withMessages([
                'order' => ['You can only review completed orders.'],
            ]);
        }

        if ($order->review) {
            throw ValidationException::withMessages([
                'order' => ['You have already reviewed this order.'],
            ]);
        }

        return Review::create([
            'user_id'        => $user->id,
            'shop_id'        => $order->shop_id,
            'order_type'     => 'print',
            'print_order_id' => $order->id,
            'rating'         => $data['rating'],
            'comment'        => $data['comment'] ?? null,
        ]);
    }

    public function submitAtkReview(User $user, string $orderId, array $data): Review
    {
        $order = AtkOrder::where('user_id', $user->id)->findOrFail($orderId);

        if ($order->status !== 'completed') {
            throw ValidationException::withMessages([
                'order' => ['You can only review completed orders.'],
            ]);
        }

        if ($order->review) {
            throw ValidationException::withMessages([
                'order' => ['You have already reviewed this order.'],
            ]);
        }

        return Review::create([
            'user_id'      => $user->id,
            'shop_id'      => $order->shop_id,
            'order_type'   => 'atk',
            'atk_order_id' => $order->id,
            'rating'       => $data['rating'],
            'comment'      => $data['comment'] ?? null,
        ]);
    }
}
