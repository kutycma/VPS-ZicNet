<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'balance', 'status', 'phone',
        'telegram_id', 'telegram_verify_token', 'email_verified_at',
        'avatar', 'google_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'decimal:2',
    ];

    // === Relationships ===

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function vpsInstances()
    {
        return $this->hasMany(VpsInstance::class);
    }

    public function depositRequests()
    {
        return $this->hasMany(DepositRequest::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // === Helpers ===

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', '.') . ' đ';
    }

    /**
     * Avatar URL
     * 1. Google avatar (lưu khi login Google)
     * 2. Gravatar + identicon fallback (mọi email)
     */
    public function getAvatarUrl($size = 64)
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=' . $size . '&d=identicon';
    }
}
