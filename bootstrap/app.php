<?php

use App\Http\Middleware\PartnerApprovedMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'              => RoleMiddleware::class,
            'partner.approved'  => PartnerApprovedMiddleware::class,
        ]);

        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $json = fn(Request $request) => $request->expectsJson();

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($json) {
            if ($json($request)) {
                return response()->json(['status' => 'error', 'message' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($json) {
            if ($json($request)) {
                return response()->json(['status' => 'error', 'message' => 'Endpoint not found.'], 404);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($json) {
            if ($json($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json) {
            if ($json($request)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($json) {
            if ($json($request)) {
                return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
            }
        });

        $exceptions->render(function (\Exception $e, Request $request) use ($json) {
            if ($json($request) && app()->environment('production')) {
                return response()->json(['status' => 'error', 'message' => 'Server error.'], 500);
            }
        });
    })->create();
