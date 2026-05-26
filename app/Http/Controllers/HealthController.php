<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Check API health.
     *
     * Returns the status of the API and its database connection.
     */
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (\Exception) {
            $dbStatus = 'error';
        }

        $status = $dbStatus === 'ok' ? 'ok' : 'degraded';

        return response()->json([
            'status'   => $status,
            'services' => [
                'database' => $dbStatus,
            ],
        ], 200);
    }
}
