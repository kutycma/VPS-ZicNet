<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'vps_plan_id', 'vps_instance_id', 'amount',
        'billing_cycle', 'duration_months', 'type', 'status', 'notes', 'api_response',
        'payload', 'attempts', 'last_attempt_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'api_response' => 'array',
        'payload' => 'array',
        'last_attempt_at' => 'datetime'
    ];

    // === Relationships ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vpsPlan()
    {
        return $this->belongsTo(VpsPlan::class, 'vps_plan_id');
    }

    public function vpsInstance()
    {
        return $this->belongsTo(VpsInstance::class, 'vps_instance_id');
    }

    // === Helpers ===

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'secondary',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public function getFormattedAmountAttribute()
    {
        return number_format((float)$this->amount, 0, ',', '.') . ' đ';
    }
}
