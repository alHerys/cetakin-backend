<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterPartnerRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->authService->registerUser($request->validated());

        return $this->success([
            'user'  => $result['user'],
            'token' => $result['token'],
        ], 'User registered successfully.', 201);
    }

    public function registerPartner(RegisterPartnerRequest $request): JsonResponse
    {
        $result = $this->authService->registerPartner(
            $request->validated(),
            $request->file('shop_photo')
        );

        return $this->success([
            'user'  => $result['user'],
            'token' => $result['token'],
        ], 'Partner registered successfully. Awaiting admin approval.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success([
            'user'  => $result['user'],
            'token' => $result['token'],
        ], 'Login successful.');
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(null, 'Logged out successfully.');
    }

    public function me(): JsonResponse
    {
        return $this->success($this->authService->me(), 'Authenticated user.');
    }
}
