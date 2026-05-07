<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'payment_method_id', 'transaction_code', 'amount', 'actual_amount',
        'status', 'bank_transaction_id', 'bank_description',
        'matched_at', 'expires_at', 'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'matched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // === Relationships ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // === Scopes ===

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>=', now());
    }

    // === Helpers ===

    /**
     * Sinh mã giao dịch tiếp theo (race-condition safe)
     * Dùng DB lock để đảm bảo 2 request đồng thời không sinh cùng mã
     */
    public static function generateTransactionCode(): string
    {
        $prefix = Setting::get('deposit_prefix', 'ZICNET');
        $maxRetries = 5;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $code = \DB::transaction(function () use ($prefix) {
                // Lock row cuối cùng để tránh 2 request đọc cùng lastCode
                $lastCode = static::where('transaction_code', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->orderByRaw('CAST(SUBSTRING(transaction_code, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
                    ->value('transaction_code');

                if ($lastCode) {
                    $lastNumber = (int) substr($lastCode, strlen($prefix));
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 3800;
                }

                return $prefix . $nextNumber;
            });

            // Double-check: mã chưa tồn tại trong DB
            if (!static::where('transaction_code', $code)->exists()) {
                return $code;
            }

            // Nếu trùng (edge case), thêm random suffix
            \Log::warning("DepositRequest: Code {$code} already exists, retrying (attempt " . ($attempt + 1) . ")");
            usleep(50000); // 50ms delay
        }

        // Fallback cuối: prefix + timestamp + random
        return $prefix . time() . rand(10, 99);
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->expires_at->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Đang chờ',
            'completed' => 'Hoàn thành',
            'expired' => 'Hết hạn',
            'cancelled' => 'Đã hủy',
            'rejected' => 'Từ chối',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'warning',
            'completed' => 'success',
            'expired' => 'secondary',
            'cancelled' => 'dark',
            'rejected' => 'danger',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', '.') . 'đ';
    }
}
