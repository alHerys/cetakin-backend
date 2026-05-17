<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public function listPartners(?string $status): LengthAwarePaginator
    {
        return Shop::with('user')
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);
    }

    public function approve(string $id): Shop
    {
        $shop = Shop::findOrFail($id);

        if ($shop->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending shops can be approved.'],
            ]);
        }

        $shop->update(['status' => 'approved']);

        return $shop;
    }

    public function reject(string $id, string $reason): Shop
    {
        $shop = Shop::findOrFail($id);

        if ($shop->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only pending shops can be rejected.'],
            ]);
        }

        $shop->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $shop;
    }
}
