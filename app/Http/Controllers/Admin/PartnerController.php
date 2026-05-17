<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminService $adminService) {}

    public function index(Request $request): JsonResponse
    {
        $partners = $this->adminService->listPartners($request->query('status'));

        return $this->paginated($partners, 'Partners retrieved successfully.');
    }

    public function approve(string $id): JsonResponse
    {
        $shop = $this->adminService->approve($id);

        return $this->success($shop, 'Partner approved successfully.');
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $shop = $this->adminService->reject($id, $request->input('reason'));

        return $this->success($shop, 'Partner rejected successfully.');
    }
}
