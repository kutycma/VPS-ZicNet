<?php

namespace App\Services\Billing;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Nạp tiền vào balance
     */
    public function deposit(User $user, float $amount, string $description = 'Nạp tiền', string $reference = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            $user = User::lockForUpdate()->find($user->id);

            $balanceBefore = $user->balance;
            $user->balance += $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => 'deposit',
                'description' => $description,
                'status' => 'completed',
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Trừ tiền khi mua VPS (bọc trong transaction + lock)
     */
    public function purchase(User $user, float $amount, string $description = 'Mua VPS', string $reference = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            $user = User::lockForUpdate()->find($user->id);

            if ($user->balance < $amount) {
                throw new \Exception('Số dư không đủ. Cần ' . number_format($amount, 0, ',', '.') . 'đ, hiện có ' . number_format($user->balance, 0, ',', '.') . 'đ');
            }

            $balanceBefore = $user->balance;
            $user->balance -= $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => 'purchase',
                'description' => $description,
                'status' => 'completed',
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Hoàn tiền khi lỗi
     */
    public function refund(User $user, float $amount, string $description = 'Hoàn tiền', string $reference = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            $user = User::lockForUpdate()->find($user->id);

            $balanceBefore = $user->balance;
            $user->balance += $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => 'refund',
                'description' => $description,
                'status' => 'completed',
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Admin điều chỉnh balance (cộng hoặc trừ)
     */
    public function adminAdjust(User $user, float $amount, string $description = 'Admin điều chỉnh'): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $user = User::lockForUpdate()->find($user->id);

            $balanceBefore = $user->balance;
            $user->balance += $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => 'admin_adjust',
                'description' => $description,
                'status' => 'completed',
            ]);
        });
    }

    /**
     * Khuyến mãi
     */
    public function promotion(User $user, float $amount, string $description = 'Khuyến mãi'): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $user = User::lockForUpdate()->find($user->id);

            $balanceBefore = $user->balance;
            $user->balance += $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'type' => 'promotion',
                'description' => $description,
                'status' => 'completed',
            ]);
        });
    }
}
