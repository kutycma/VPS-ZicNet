<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'balance_before', 'balance_after',
        'type', 'description', 'status', 'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // === Relationships ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // === Helpers ===

    public function isCredit()
    {
        return in_array($this->type, ['deposit', 'refund', 'admin_adjust', 'promotion']);
    }

    public function getTypeLabelAttribute()
    {
        $labels = [
            'deposit' => 'Nạp tiền',
            'purchase' => 'Mua VPS',
            'refund' => 'Hoàn tiền',
            'renewal' => 'Gia hạn',
            'admin_adjust' => 'Admin điều chỉnh',
            'promotion' => 'Khuyến mãi',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getTypeBadgeAttribute()
    {
        $badges = [
            'deposit' => 'success',
            'purchase' => 'primary',
            'refund' => 'warning',
            'renewal' => 'info',
            'admin_adjust' => 'secondary',
            'promotion' => 'success',
        ];
        return $badges[$this->type] ?? 'secondary';
    }

    public function getFormattedAmountAttribute()
    {
        $prefix = $this->isCredit() ? '+' : '-';
        return $prefix . number_format(abs($this->amount), 0, ',', '.') . ' đ';
    }
}
