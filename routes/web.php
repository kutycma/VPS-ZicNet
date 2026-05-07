<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Auth routes
Auth::routes(['verify' => true]);

// Socialite Google Auth Routes
Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback']);

// Redirect root to welcome page with plans
Route::get('/', function (\Illuminate\Http\Request $request) {
    $selectedGroup = $request->query('group');
    $groups = collect();
    $plans = collect();

    try {
        $requiredTables = ['vps_plan_groups', 'vps_plans', 'vps_providers'];
        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return view('welcome', compact('plans', 'groups', 'selectedGroup'));
            }
        }

        $groups = \App\Models\VpsPlanGroup::where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $query = \App\Models\VpsPlan::with(['provider', 'group'])
            ->where('status', 'active')
            ->whereHas('provider', function ($q) {
                $q->where('status', 'active');
            });

        if ($selectedGroup) {
            $groupModel = \App\Models\VpsPlanGroup::where('slug', $selectedGroup)->first();
            if ($groupModel) {
                $query->where('group_id', $groupModel->id);
            }
        }

        $plans = $query
            ->orderBy('sort_order')
            ->orderBy('type')
            ->orderBy('selling_price')
            ->get()
            ->groupBy('type');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Unable to load public VPS plans.', [
            'message' => $e->getMessage(),
        ]);
    }

    return view('welcome', compact('plans', 'groups', 'selectedGroup'));
})->name('home');

// =============================================
// ADMIN ROUTES
// =============================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'is_admin'])->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/adjust-balance', [\App\Http\Controllers\Admin\UserController::class, 'adjustBalance'])->name('users.adjust-balance');

    // (Obsolete VPS Groups removed)

    // VPS Providers
    Route::resource('providers', \App\Http\Controllers\Admin\VpsProviderController::class);
    Route::post('/providers/{provider}/test-connection', [\App\Http\Controllers\Admin\VpsProviderController::class, 'testConnection'])->name('providers.test-connection');
    Route::post('/providers/{provider}/sync-plans', [\App\Http\Controllers\Admin\VpsProviderController::class, 'syncPlans'])->name('providers.sync-plans');
    Route::post('/providers/{provider}/sync-instances', [\App\Http\Controllers\Admin\VpsProviderController::class, 'syncInstances'])->name('providers.sync-instances');
    Route::get('/providers/{provider}/agency-info', [\App\Http\Controllers\Admin\VpsProviderController::class, 'agencyInfo'])->name('providers.agency-info');

    // VPS Plan Groups (Tabs)
    Route::resource('plan-groups', \App\Http\Controllers\Admin\VpsPlanGroupController::class);

    // VPS Plans
    Route::patch('/plans/{plan}/toggle-status', [\App\Http\Controllers\Admin\VpsPlanController::class, 'toggleStatus'])->name('plans.toggle-status');
    Route::resource('plans', \App\Http\Controllers\Admin\VpsPlanController::class);
    Route::post('/plans/calculate-price', [\App\Http\Controllers\Admin\VpsPlanController::class, 'calculatePrice'])->name('plans.calculate-price');

    // Coupons
    Route::patch('/coupons/{coupon}/toggle-status', [\App\Http\Controllers\Admin\CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
    Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);

    // VPS Manager (instances)
    Route::get('/vps', [\App\Http\Controllers\Admin\VpsManagerController::class, 'index'])->name('vps.index');
    Route::get('/vps/{instance}', [\App\Http\Controllers\Admin\VpsManagerController::class, 'show'])->name('vps.show');
    Route::post('/vps/{instance}/action', [\App\Http\Controllers\Admin\VpsManagerController::class, 'action'])->name('vps.action');
    
    Route::get('/vps/{instance}/rebuild', [\App\Http\Controllers\Admin\VpsManagerController::class, 'rebuild'])->name('vps.rebuild');
    Route::post('/vps/{instance}/rebuild', [\App\Http\Controllers\Admin\VpsManagerController::class, 'confirmRebuild']);
    
    Route::get('/vps/{instance}/renew', [\App\Http\Controllers\Admin\VpsManagerController::class, 'renew'])->name('vps.renew');
    Route::post('/vps/{instance}/renew', [\App\Http\Controllers\Admin\VpsManagerController::class, 'confirmRenew']);

    Route::get('/vps/{instance}/upgrade', [\App\Http\Controllers\Admin\VpsManagerController::class, 'upgrade'])->name('vps.upgrade');
    Route::post('/vps/{instance}/upgrade', [\App\Http\Controllers\Admin\VpsManagerController::class, 'confirmUpgrade']);

    Route::post('/vps/{instance}/assign', [\App\Http\Controllers\Admin\VpsManagerController::class, 'assignUser'])->name('vps.assign');

    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('transactions.index');

    // Orders
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('orders.cancel');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

    // Payment Config
    Route::get('/payment', [\App\Http\Controllers\Admin\PaymentConfigController::class, 'index'])->name('payment.index');
    Route::post('/payment', [\App\Http\Controllers\Admin\PaymentConfigController::class, 'store'])->name('payment.store');
    Route::put('/payment/{method}', [\App\Http\Controllers\Admin\PaymentConfigController::class, 'update'])->name('payment.update');
    Route::delete('/payment/{method}', [\App\Http\Controllers\Admin\PaymentConfigController::class, 'destroy'])->name('payment.destroy');

    // Deposit Management
    Route::get('/deposits', [\App\Http\Controllers\Admin\DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits/{deposit}/approve', [\App\Http\Controllers\Admin\DepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/deposits/{deposit}/reject', [\App\Http\Controllers\Admin\DepositController::class, 'reject'])->name('deposits.reject');
    Route::post('/deposits/check-now', [\App\Http\Controllers\Admin\DepositController::class, 'checkNow'])->name('deposits.check-now');
    
    // Tickets
    Route::get('/tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\Admin\TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/close', [\App\Http\Controllers\Admin\TicketController::class, 'close'])->name('tickets.close');

    // Themes
    Route::get('/themes', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('themes.index');
    Route::get('/themes/create', [\App\Http\Controllers\Admin\ThemeController::class, 'create'])->name('themes.create');
    Route::post('/themes', [\App\Http\Controllers\Admin\ThemeController::class, 'store'])->name('themes.store');
    Route::get('/themes/{theme}/edit', [\App\Http\Controllers\Admin\ThemeController::class, 'edit'])->name('themes.edit');
    Route::put('/themes/{theme}', [\App\Http\Controllers\Admin\ThemeController::class, 'update'])->name('themes.update');
    Route::delete('/themes/{theme}', [\App\Http\Controllers\Admin\ThemeController::class, 'destroy'])->name('themes.destroy');
    Route::post('/themes/{theme}/activate', [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('themes.activate');
    Route::get('/themes/{theme}/preview-css', [\App\Http\Controllers\Admin\ThemeController::class, 'previewCss'])->name('themes.preview-css');
});

// =============================================
// PUBLIC CLIENT ROUTES
// =============================================
Route::prefix('client')->name('client.')->group(function () {
    // Orders (Public viewing)
    Route::get('/plans', [\App\Http\Controllers\Client\OrderController::class, 'plans'])->name('orders.plans');
});

// =============================================
// CLIENT ROUTES
// =============================================
Route::prefix('client')->name('client.')->middleware(['auth'])->group(function () {

    Route::get('/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Client\UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Client\UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [\App\Http\Controllers\Client\UserController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/unlink-telegram', [\App\Http\Controllers\Client\UserController::class, 'unlinkTelegram'])->name('profile.unlink-telegram');

    // VPS
    Route::get('/vps', [\App\Http\Controllers\Client\VpsController::class, 'index'])->name('vps.index');
    Route::get('/vps/{instance}', [\App\Http\Controllers\Client\VpsController::class, 'show'])->name('vps.show');
    Route::post('/vps/{instance}/action', [\App\Http\Controllers\Client\VpsController::class, 'action'])->name('vps.action');
    Route::get('/vps/{instance}/rebuild', [\App\Http\Controllers\Client\VpsController::class, 'rebuild'])->name('vps.rebuild');
    Route::post('/vps/{instance}/rebuild', [\App\Http\Controllers\Client\VpsController::class, 'confirmRebuild'])->name('vps.rebuild.confirm');
    Route::get('/vps/{instance}/renew', [\App\Http\Controllers\Client\VpsController::class, 'renew'])->name('vps.renew');
    Route::post('/vps/{instance}/renew', [\App\Http\Controllers\Client\VpsController::class, 'confirmRenew'])->name('vps.renew.confirm');
    Route::get('/vps/{instance}/upgrade', [\App\Http\Controllers\Client\VpsController::class, 'upgrade'])->name('vps.upgrade');
    Route::post('/vps/{instance}/upgrade', [\App\Http\Controllers\Client\VpsController::class, 'confirmUpgrade'])->name('vps.upgrade.confirm');

    // Orders Actions
    Route::get('/order/{plan}', [\App\Http\Controllers\Client\OrderController::class, 'create'])->name('orders.create');
    Route::post('/order', [\App\Http\Controllers\Client\OrderController::class, 'store'])->name('orders.store');
    Route::post('/order/apply-coupon', [\App\Http\Controllers\Client\OrderController::class, 'applyCoupon'])->name('orders.apply-coupon');
    Route::get('/orders/history', [\App\Http\Controllers\Client\OrderController::class, 'history'])->name('orders.history');

    // Billing
    Route::get('/billing', [\App\Http\Controllers\Client\BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/deposit', [\App\Http\Controllers\Client\BillingController::class, 'deposit'])->name('billing.deposit');
    Route::post('/billing/deposit', [\App\Http\Controllers\Client\BillingController::class, 'storeDeposit'])->name('billing.deposit.store');
    Route::get('/billing/deposit/{deposit}/waiting', [\App\Http\Controllers\Client\BillingController::class, 'waitingDeposit'])->name('billing.deposit.waiting');
    Route::get('/billing/deposit/{deposit}/status', [\App\Http\Controllers\Client\BillingController::class, 'checkDepositStatus'])->name('billing.deposit.status');
    Route::post('/billing/deposit/{deposit}/cancel', [\App\Http\Controllers\Client\BillingController::class, 'cancelDeposit'])->name('billing.deposit.cancel');

    // Tickets
    Route::get('/tickets', [\App\Http\Controllers\Client\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\Client\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\Client\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Client\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Client\TicketController::class, 'reply'])->name('tickets.reply');
});

// =============================================
// WEBHOOK ROUTES (no auth)
// =============================================
Route::prefix('webhook')->name('webhook.')->group(function () {
    Route::post('/payment', [\App\Http\Controllers\Webhook\PaymentWebhookController::class, 'handle'])->name('payment');
    Route::post('/telegram', [\App\Http\Controllers\Webhook\TelegramWebhookController::class, 'handle'])->name('telegram');
});
