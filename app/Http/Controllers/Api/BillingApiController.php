<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingApiController extends Controller
{
    /**
     * GET /api/billing
     */
    public function index()
    {
        $user           = Auth::user();
        $transactions   = $user->transactions()->latest()->paginate(20);
        $recentDeposits = DepositRequest::where('user_id', $user->id)->latest()->take(5)->get();

        return response()->json([
            'balance'           => (float) $user->balance,
            'balance_formatted' => number_format((float) $user->balance, 0, ',', '.') . 'đ',
            'transactions'      => [
                'data' => array_map(function ($t) {
                    return [
                        'id'               => $t->id,
                        'type'             => $t->type,
                        'amount'           => (float) $t->amount,
                        'amount_formatted' => number_format((float) $t->amount, 0, ',', '.') . 'đ',
                        'description'      => $t->description,
                        'created_at'       => $t->created_at ? $t->created_at->toISOString() : null,
                    ];
                }, $transactions->items()),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page'    => $transactions->lastPage(),
                    'total'        => $transactions->total(),
                ],
            ],
            'recent_deposits' => $recentDeposits->map(function ($d) {
                return $this->formatDeposit($d);
            })->values()->all(),
        ]);
    }

    /**
     * GET /api/billing/methods
     */
    public function methods()
    {
        $methods = PaymentMethod::active()->orderBy('sort_order')->get()
            ->map(function ($m) {
                return [
                    'id'          => $m->id,
                    'name'        => $m->name,
                    'type'        => $m->type,
                    'description' => $m->description,
                    'min_amount'  => $m->min_amount,
                    'max_amount'  => $m->max_amount,
                ];
            });

        return response()->json($methods);
    }

    /**
     * POST /api/billing/deposit
     */
    public function storeDeposit(Request $request)
    {
        $request->validate([
            'amount'            => 'required|numeric|min:10000|max:100000000',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $user          = Auth::user();
        $paymentMethod = PaymentMethod::active()->find($request->payment_method_id);

        if (!$paymentMethod) {
            return response()->json(['message' => 'Payment method not available.'], 422);
        }

        $pendingDeposit = DepositRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>=', now())
            ->first();

        if ($pendingDeposit) {
            return response()->json([
                'message' => 'You already have a pending deposit.',
                'deposit' => $this->formatDeposit($pendingDeposit),
            ], 409);
        }

        $expiryMinutes = (int) Setting::get('deposit_expiry_minutes', 30);

        try {
            $deposit = DB::transaction(function () use ($user, $paymentMethod, $request, $expiryMinutes) {
                $existing = DepositRequest::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->where('expires_at', '>=', now())
                    ->lockForUpdate()
                    ->first();
                if ($existing) return $existing;

                return DepositRequest::create([
                    'user_id'           => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_code'  => DepositRequest::generateTransactionCode(),
                    'amount'            => (float) $request->amount,
                    'status'            => 'pending',
                    'expires_at'        => now()->addMinutes($expiryMinutes),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                $deposit = DepositRequest::create([
                    'user_id'           => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_code'  => DepositRequest::generateTransactionCode(),
                    'amount'            => (float) $request->amount,
                    'status'            => 'pending',
                    'expires_at'        => now()->addMinutes($expiryMinutes),
                ]);
            } else {
                throw $e;
            }
        }

        return response()->json([
            'deposit'        => $this->formatDeposit($deposit),
            'payment_method' => $this->formatPaymentMethodDetail($paymentMethod, $deposit),
        ], 201);
    }

    /**
     * GET /api/billing/deposit/{deposit}
     */
    public function depositStatus(DepositRequest $deposit)
    {
        if ($deposit->user_id !== Auth::id()) abort(403);

        $deposit->refresh();

        if ($deposit->status === 'pending' && $deposit->expires_at->isPast()) {
            $deposit->update(['status' => 'expired']);
        }

        return response()->json([
            'status'                  => $deposit->status,
            'actual_amount'           => $deposit->actual_amount,
            'actual_amount_formatted' => $deposit->actual_amount
                ? number_format((float) $deposit->actual_amount, 0, ',', '.') . 'đ'
                : null,
            'remaining_seconds'       => $deposit->status === 'pending'
                ? max(0, now()->diffInSeconds($deposit->expires_at, false))
                : 0,
            'new_balance'             => $deposit->status === 'completed'
                ? (float) Auth::user()->fresh()->balance
                : null,
        ]);
    }

    /**
     * POST /api/billing/deposit/{deposit}/cancel
     */
    public function cancelDeposit(DepositRequest $deposit)
    {
        if ($deposit->user_id !== Auth::id()) abort(403);

        if ($deposit->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel this deposit.'], 422);
        }

        $deposit->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Deposit cancelled.']);
    }

    private function formatDeposit(DepositRequest $deposit): array
    {
        return [
            'id'                   => $deposit->id,
            'transaction_code'     => $deposit->transaction_code,
            'amount'               => (float) $deposit->amount,
            'amount_formatted'     => number_format((float) $deposit->amount, 0, ',', '.') . 'đ',
            'actual_amount'        => $deposit->actual_amount,
            'status'               => $deposit->status,
            'expires_at'           => $deposit->expires_at ? $deposit->expires_at->toISOString() : null,
            'expires_at_formatted' => $deposit->expires_at ? $deposit->expires_at->format('d/m/Y H:i') : null,
            'created_at'           => $deposit->created_at ? $deposit->created_at->toISOString() : null,
        ];
    }

    private function formatPaymentMethodDetail(PaymentMethod $method, DepositRequest $deposit): array
    {
        return [
            'id'               => $method->id,
            'name'             => $method->name,
            'type'             => $method->type,
            'account_number'   => $method->account_number ?? null,
            'account_name'     => $method->account_name ?? null,
            'bank_name'        => $method->bank_name ?? null,
            'qr_content'       => $method->qr_content ?? null,
            'transaction_code' => $deposit->transaction_code,
            'amount'           => (float) $deposit->amount,
        ];
    }
}
