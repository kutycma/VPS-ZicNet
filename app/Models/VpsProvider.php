<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'api_endpoint', 'api_username', 'api_app', 'api_secret',
        'auth_token', 'token_expires_at', 'markup_type', 'markup_value',
        'status', 'description', 'default_group_id',
    ];

    protected $hidden = [
        'api_secret', 'auth_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'markup_value' => 'decimal:2',
    ];

    // === Relationships ===

    public function defaultGroup()
    {
        return $this->belongsTo(VpsPlanGroup::class, 'default_group_id');
    }

    public function plans()
    {
        return $this->hasMany(VpsPlan::class, 'provider_id');
    }

    public function instances()
    {
        return $this->hasMany(VpsInstance::class, 'provider_id');
    }

    // === Helpers ===

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isTokenValid()
    {
        return $this->auth_token && $this->token_expires_at && $this->token_expires_at->isFuture();
    }

    /**
     * Tính giá bán dựa trên giá vốn và markup config
     */
    public function calculateSellingPrice($providerPrice)
    {
        switch ($this->markup_type) {
            case 'percentage':
                return round($providerPrice * (1 + $this->markup_value / 100), 0);
            case 'fixed':
                return $providerPrice + $this->markup_value;
            case 'manual':
            default:
                return $providerPrice;
        }
    }
}
