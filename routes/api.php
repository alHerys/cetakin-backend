<?php

use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Discovery\DiscoveryController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Shop\AtkProductController;
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\ShopPricingController;
use App\Http\Controllers\Shop\ShopServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/register/partner', [AuthController::class, 'registerPartner']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::prefix('shops')->middleware(['auth:api', 'role:partner', 'partner.approved'])->group(function () {
        Route::get('/me', [ShopController::class, 'show']);
        Route::put('/me', [ShopController::class, 'update']);
        Route::put('/me/services', [ShopServiceController::class, 'update']);
        Route::put('/me/pricing', [ShopPricingController::class, 'update']);
        Route::apiResource('/me/atk', AtkProductController::class)->parameters(['atk' => 'id']);
    });

    Route::prefix('shops')->middleware(['auth:api', 'role:user'])->group(function () {
        Route::get('/', [DiscoveryController::class, 'index']);
        Route::get('/{id}', [DiscoveryController::class, 'show']);
        Route::get('/{id}/atk', [DiscoveryController::class, 'atkCatalog']);
        Route::get('/{id}/reviews', [ReviewController::class, 'shopReviews']);
    });

    Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
        Route::get('/partners', [AdminPartnerController::class, 'index']);
        Route::patch('/partners/{id}/approve', [AdminPartnerController::class, 'approve']);
        Route::patch('/partners/{id}/reject', [AdminPartnerController::class, 'reject']);
    });
});
