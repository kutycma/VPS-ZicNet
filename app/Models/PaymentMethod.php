<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'gateway', 'account_name', 'account_number',
        'bank_name', 'api_key', 'secret_key', 'api_url',
        'instructions', 'is_active', 'sort_order',
    ];

    protected $hidden = [
        'api_key', 'secret_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // === Relationships ===

    public function depositRequests()
    {
        return $this->hasMany(DepositRequest::class);
    }

    // === Scopes ===

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this method has a configured API for auto-verification
     */
    public function hasApiConfig(): bool
    {
        return !empty($this->api_key) && !empty($this->api_url);
    }

    /**
     * Get the full API URL with key appended
     */
    public function getFullApiUrl(): ?string
    {
        if (!$this->hasApiConfig()) {
            return null;
        }
        return rtrim($this->api_url, '/') . '/' . $this->api_key;
    }
}
