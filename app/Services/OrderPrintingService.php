<?php

namespace App\Services;

use App\Models\PrintOrder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPrintingService
{
    const VALID_TRANSITIONS = [
        'pending'          => 'confirmed',
        'confirmed'        => 'processing',
        'processing'       => 'ready_for_pickup',
        'ready_for_pickup' => 'completed',
    ];

    public function __construct(private CloudinaryService $cloudinary) {}

    public function createOrder(User $user, array $data, UploadedFile $file): PrintOrder
    {
        $shop    = Shop::findOrFail($data['shop_id']);
        $pricing = $shop->pricing;

        if (!$pricing) {
            throw ValidationException::withMessages([
                'shop_id' => ['This shop has not set up pricing yet.'],
            ]);
        }

        // Upload file outside transaction to avoid holding DB locks during slow network I/O
        $fileUrl = $this->cloudinary->upload($file, 'print-orders');

        return DB::transaction(function () use ($user, $data, $shop, $pricing, $fileUrl) {
            $finalPrice = $this->calculatePrice($pricing, $data);

            return PrintOrder::create([
                'user_id'     => $user->id,
                'shop_id'     => $shop->id,
                'file_url'    => $fileUrl,
                'paper_size'  => $data['paper_size'],
                'color_mode'  => $data['color_mode'],
                'sides'       => $data['sides'],
                'binding'     => $data['binding'],
                'copies'      => $data['copies'],
                'total_pages' => $data['total_pages'],
                'final_price' => $finalPrice,
                'notes'       => $data['notes'] ?? null,
                'status'      => 'pending',
            ]);
        });
    }

    public function listOrders(User $user, ?string $status): LengthAwarePaginator
    {
        return PrintOrder::where('user_id', $user->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with('shop')
            ->latest()
            ->paginate(15);
    }

    public function getOrder(User $user, string $id): PrintOrder
    {
        return PrintOrder::where('user_id', $user->id)
            ->with(['shop', 'statusHistory', 'review'])
            ->findOrFail($id);
    }

    public function cancelOrder(User $user, string $id): PrintOrder
    {
        $order = PrintOrder::where('user_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending orders can be cancelled.'],
            ]);
        }

        $order->update(['status' => 'cancelled']);

        return $order;
    }

    public function updateStatus(Shop $shop, string $orderId, string $newStatus): PrintOrder
    {
        $order = PrintOrder::where('shop_id', $shop->id)->findOrFail($orderId);

        $expected = self::VALID_TRANSITIONS[$order->status] ?? null;

        if ($expected !== $newStatus) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from '{$order->status}' to '{$newStatus}'."],
            ]);
        }

        $order->update(['status' => $newStatus]);

        return $order;
    }

    private function calculatePrice(object $pricing, array $specs): int
    {
        $perPage = $specs['color_mode'] === 'full_color'
            ? $pricing->full_color_per_page
            : $pricing->black_and_white_per_page;

        $base        = $perPage * $specs['total_pages'] * $specs['copies'];
        $sideExtra   = $specs['sides'] === 'double' ? $pricing->double_side_surcharge * $specs['copies'] : 0;
        $bindingPrices = $pricing->binding_prices;
        $bindingExtra  = $bindingPrices[$specs['binding']] ?? 0;

        return $base + $sideExtra + $bindingExtra;
    }
}
