<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PrintOrder;
use App\Services\OrderPrintingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerPrintOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderPrintingService $orderPrintingService) {}

    public function index(Request $request): JsonResponse
    {
        $shop = auth()->user()->shop;

        $orders = PrintOrder::where('shop_id', $shop->id)
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->paginated($orders, 'Print orders retrieved successfully.');
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:confirmed,processing,ready_for_pickup,completed'],
        ]);

        $order = $this->orderPrintingService->updateStatus(
            auth()->user()->shop,
            $id,
            $request->input('status')
        );

        return $this->success($order, 'Order status updated successfully.');
    }
}
