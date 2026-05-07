<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ThemeApiController;
use App\Http\Controllers\Api\PlanApiController;
use App\Http\Controllers\Api\VpsApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\BillingApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\DashboardApiController;

/*
|--------------------------------------------------------------------------
| API Routes — Theme Contract
|--------------------------------------------------------------------------
| These endpoints are the "contract" between the Laravel backend and any
| frontend theme (Blade, Next.js, Vue, etc.).
|
| Authentication: Sanctum token (Bearer) or Sanctum SPA cookie.
| All responses are JSON.
|--------------------------------------------------------------------------
*/

// ═══════════════════════════════════════════════════════
// PUBLIC ENDPOINTS — no auth required
// ═══════════════════════════════════════════════════════

// Active theme — returns CSS variables + custom CSS
Route::get('/theme/active', [ThemeApiController::class, 'active']);

// VPS Plans (public listing — used on landing page)
Route::get('/plan-groups', [PlanApiController::class, 'groups']);
Route::get('/plans', [PlanApiController::class, 'index']);
Route::get('/plans/{plan}', [PlanApiController::class, 'show']);

// ═══════════════════════════════════════════════════════
// AUTH ENDPOINTS
// ═══════════════════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/me',      [AuthApiController::class, 'me']);
    });
});

// ═══════════════════════════════════════════════════════
// AUTHENTICATED ENDPOINTS  (Sanctum token required)
// ═══════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // VPS Management
    Route::prefix('vps')->group(function () {
        Route::get('/',                         [VpsApiController::class, 'index']);
        Route::get('/{instance}',               [VpsApiController::class, 'show']);
        Route::post('/{instance}/action',       [VpsApiController::class, 'action']);
        Route::get('/{instance}/rebuild',       [VpsApiController::class, 'getRebuild']);
        Route::post('/{instance}/rebuild',      [VpsApiController::class, 'confirmRebuild']);
        Route::get('/{instance}/renew',         [VpsApiController::class, 'getRenew']);
        Route::post('/{instance}/renew',        [VpsApiController::class, 'confirmRenew']);
        Route::get('/{instance}/upgrade',       [VpsApiController::class, 'getUpgrade']);
        Route::post('/{instance}/upgrade',      [VpsApiController::class, 'confirmUpgrade']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/',               [OrderApiController::class, 'index']);
        Route::post('/',              [OrderApiController::class, 'store']);
        Route::post('/apply-coupon',  [OrderApiController::class, 'applyCoupon']);
    });

    // Billing
    Route::prefix('billing')->group(function () {
        Route::get('/',                         [BillingApiController::class, 'index']);
        Route::get('/methods',                  [BillingApiController::class, 'methods']);
        Route::post('/deposit',                 [BillingApiController::class, 'storeDeposit']);
        Route::get('/deposit/{deposit}',        [BillingApiController::class, 'depositStatus']);
        Route::post('/deposit/{deposit}/cancel',[BillingApiController::class, 'cancelDeposit']);
    });

    // Support Tickets
    Route::prefix('tickets')->group(function () {
        Route::get('/',                     [TicketApiController::class, 'index']);
        Route::post('/',                    [TicketApiController::class, 'store']);
        Route::get('/{ticket}',             [TicketApiController::class, 'show']);
        Route::post('/{ticket}/reply',      [TicketApiController::class, 'reply']);
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/',          [ProfileApiController::class, 'show']);
        Route::put('/',          [ProfileApiController::class, 'update']);
        Route::post('/password', [ProfileApiController::class, 'changePassword']);
    });
});
