<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateAtkOrderRequest;
use App\Services\OrderAtkService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtkOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderAtkService $orderAtkService) {}

    public function store(CreateAtkOrderRequest $request): JsonResponse
    {
        $order = $this->orderAtkService->createOrder(
            auth()->user(),
            $request->validated()
        );

        return $this->success($order, 'ATK order placed successfully.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderAtkService->listOrders(
            auth()->user(),
            $request->query('status')
        );

        return $this->paginated($orders, 'ATK orders retrieved successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderAtkService->getOrder(auth()->user(), $id);

        return $this->success($order, 'ATK order retrieved successfully.');
    }
}
