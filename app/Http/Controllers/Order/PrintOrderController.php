<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreatePrintOrderRequest;
use App\Services\OrderPrintingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderPrintingService $orderPrintingService) {}

    public function store(CreatePrintOrderRequest $request): JsonResponse
    {
        $order = $this->orderPrintingService->createOrder(
            auth()->user(),
            $request->validated(),
            $request->file('file')
        );

        return $this->success($order, 'Print order placed successfully.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderPrintingService->listOrders(
            auth()->user(),
            $request->query('status')
        );

        return $this->paginated($orders, 'Print orders retrieved successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderPrintingService->getOrder(auth()->user(), $id);

        return $this->success($order, 'Print order retrieved successfully.');
    }

    public function cancel(string $id): JsonResponse
    {
        $order = $this->orderPrintingService->cancelOrder(auth()->user(), $id);

        return $this->success($order, 'Print order cancelled successfully.');
    }
}
