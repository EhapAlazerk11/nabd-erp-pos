<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NabdBridge\Http\Controllers\NabdAuthController;
use Modules\NabdBridge\Http\Controllers\NabdCustomerController;
use Modules\NabdBridge\Http\Controllers\NabdOrderController;
use Modules\NabdBridge\Http\Controllers\NabdProductController;
use Modules\NabdBridge\Http\Controllers\NabdReportController;
use Modules\NabdBridge\Http\Middleware\NabdTokenMiddleware;

/*
|--------------------------------------------------------------------------
| Nabd Bridge — Token-managed admin routes (requires auth:sanctum)
|--------------------------------------------------------------------------
| These routes let an authenticated NexoPOS user manage Nabd API tokens.
| They live under /api/nabd/tokens and require a normal NexoPOS session.
*/
Route::middleware([SubstituteBindings::class, 'auth:sanctum'])
    ->prefix('nabd/tokens')
    ->group(function (): void {
        Route::get('/', [NabdAuthController::class, 'index']);
        Route::post('/', [NabdAuthController::class, 'store']);
        Route::delete('/{id}', [NabdAuthController::class, 'destroy'])
            ->where('id', '[0-9]+');
    });

/*
|--------------------------------------------------------------------------
| Nabd Bridge — Public API routes (require Nabd API Token)
|--------------------------------------------------------------------------
| All routes below are authenticated via NabdTokenMiddleware.
| Tokens are created by the admin routes above.
*/
Route::middleware([SubstituteBindings::class, NabdTokenMiddleware::class])
    ->prefix('nabd')
    ->group(function (): void {

        // ── Health check ──────────────────────────────────────────────────
        Route::get('ping', fn () => response()->json([
            'status' => 'success',
            'data' => [
                'service' => 'NabdBridge',
                'version' => '1.0.0',
                'nexopos_version' => config('app.version', 'unknown'),
                'timestamp' => now()->toIso8601String(),
            ],
        ]));

        // ── Products ──────────────────────────────────────────────────────
        Route::get('products', [NabdProductController::class, 'index']);
        Route::get('products/{id}', [NabdProductController::class, 'show'])
            ->where('id', '[0-9]+');
        Route::get('products/{id}/stock', [NabdProductController::class, 'stock'])
            ->where('id', '[0-9]+');

        // ── Categories ───────────────────────────────────────────────────
        Route::get('categories', [NabdProductController::class, 'categories']);

        // ── Orders ───────────────────────────────────────────────────────
        Route::get('orders', [NabdOrderController::class, 'index']);
        Route::get('orders/{id}', [NabdOrderController::class, 'show'])
            ->where('id', '[0-9]+');
        Route::post('orders', [NabdOrderController::class, 'store']);
        Route::patch('orders/{id}/status', [NabdOrderController::class, 'updateStatus'])
            ->where('id', '[0-9]+');

        // ── Customers ────────────────────────────────────────────────────
        Route::get('customers', [NabdCustomerController::class, 'index']);
        Route::get('customers/{id}', [NabdCustomerController::class, 'show'])
            ->where('id', '[0-9]+');
        Route::post('customers', [NabdCustomerController::class, 'store']);
        Route::put('customers/{id}', [NabdCustomerController::class, 'update'])
            ->where('id', '[0-9]+');
        Route::get('customers/{id}/orders', [NabdCustomerController::class, 'orders'])
            ->where('id', '[0-9]+');

        // ── Reports ──────────────────────────────────────────────────────
        Route::get('reports/summary', [NabdReportController::class, 'summary']);
        Route::get('reports/sales', [NabdReportController::class, 'sales']);
        Route::get('reports/low-stock', [NabdReportController::class, 'lowStock']);
        Route::get('reports/stock', [NabdReportController::class, 'stock']);
        Route::get('reports/products', [NabdReportController::class, 'products']);
    });
