<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DiscoveryService
{
    public function searchShops(array $filters): LengthAwarePaginator
    {
        $lat    = $filters['lat'];
        $lng    = $filters['lng'];
        $radius = $filters['radius'] ?? 10;

        $sub = DB::table('shops')->selectRaw("
            *,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance_km
        ", [$lat, $lng, $lat])
            ->where('status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $query = Shop::fromSub($sub, 'shops')
            ->where('distance_km', '<=', $radius)
            ->orderBy('distance_km');

        if (!empty($filters['min_rating'])) {
            $query->where('average_rating', '>=', $filters['min_rating']);
        }

        return $query->paginate(15);
    }

    public function getShop(string $id): Shop
    {
        return Shop::with(['service', 'pricing'])
            ->where('status', 'approved')
            ->findOrFail($id);
    }

    public function getAtkCatalog(string $id): LengthAwarePaginator
    {
        $shop = Shop::where('status', 'approved')->findOrFail($id);

        return $shop->atkProducts()
            ->where('is_available', true)
            ->paginate(15);
    }
}
