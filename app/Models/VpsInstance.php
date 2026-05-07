<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plan_id', 'provider_id', 'vps_provider_id',
        'ip_address', 'os', 'username', 'password', 'hostname',
        'status', 'auto_renew', 'expires_at', 'created_at_provider', 'provider_data',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'expires_at' => 'datetime',
        'created_at_provider' => 'datetime',
        'provider_data' => 'array',
    ];

    // === Relationships ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(VpsPlan::class, 'plan_id');
    }

    public function provider()
    {
        return $this->belongsTo(VpsProvider::class, 'provider_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'vps_instance_id');
    }

    // === Helpers ===

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'progressing' => 'info',
            'active' => 'success',
            'stopped' => 'secondary',
            'expired' => 'danger',
            'cancelled' => 'dark',
            'deleted' => 'secondary',
        ];
        return $badges[$this->status] ?? 'secondary';
    }
}
