<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerApprovedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $shop = auth()->user()->shop;

        if (!$shop || $shop->status !== 'approved') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your shop is not approved yet.',
            ], 403);
        }

        return $next($request);
    }
}
