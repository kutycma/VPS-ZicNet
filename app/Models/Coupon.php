<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'type', 'value',
        'min_order_amount', 'max_discount',
        'max_uses', 'max_uses_per_user', 'used_count',
        'plan_id', 'group_id', 'user_id',
        'starts_at', 'expires_at', 'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // === Relationships ===

    public function plan()
    {
        return $this->belongsTo(VpsPlan::class, 'plan_id');
    }

    public function group()
    {
        return $this->belongsTo(VpsPlanGroup::class, 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    // === Helpers ===

    /**
     * Check coupon is currently valid (active + within date range)
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    /**
     * Check if a user can use this coupon
     */
    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isValid()) return false;

        // Exclusive user check
        if ($this->user_id && $this->user_id !== $user->id) return false;

        // Per-user usage limit
        if ($this->max_uses_per_user !== null) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->max_uses_per_user) return false;
        }

        return true;
    }

    /**
     * Check if coupon applies to a specific plan
     */
    public function appliesTo(VpsPlan $plan): bool
    {
        // Plan restriction
        if ($this->plan_id && $this->plan_id !== $plan->id) return false;

        // Group restriction
        if ($this->group_id && $plan->group_id !== $this->group_id) return false;

        return true;
    }

    /**
     * Calculate discount amount for a given order total
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->min_order_amount > 0 && $amount < (float) $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = $amount * (float) $this->value / 100;
            // Cap discount at max_discount
            if ($this->max_discount && $discount > (float) $this->max_discount) {
                $discount = (float) $this->max_discount;
            }
        } else {
            // Fixed amount
            $discount = (float) $this->value;
        }

        // Cannot discount more than order total
        return min($discount, $amount);
    }

    /**
     * Record usage of this coupon
     */
    public function recordUsage(User $user, $orderId, float $discountAmount): CouponUsage
    {
        $this->increment('used_count');

        return $this->usages()->create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
        ]);
    }

    // === Display Helpers ===

    public function getTypeLabel(): string
    {
        return $this->type === 'percent' ? 'Giảm %' : 'Giảm tiền';
    }

    public function getValueDisplay(): string
    {
        if ($this->type === 'percent') {
            return $this->value . '%';
        }
        return number_format($this->value, 0, ',', '.') . 'đ';
    }

    public function getUsageDisplay(): string
    {
        $used = $this->used_count;
        $max = $this->max_uses ?? '∞';
        return "{$used}/{$max}";
    }

    public function getRestrictionDisplay(): string
    {
        $parts = [];
        if ($this->plan) $parts[] = 'Gói: ' . $this->plan->name;
        if ($this->group) $parts[] = 'Nhóm: ' . $this->group->name;
        if ($this->user) $parts[] = 'User: ' . $this->user->email;
        return implode(', ', $parts) ?: 'Tất cả';
    }
}
