<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsPlanGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'icon', 'sort_order', 'status',
    ];

    // === Relationships ===

    public function plans()
    {
        return $this->hasMany(VpsPlan::class, 'group_id');
    }

    public function activePlans()
    {
        return $this->hasMany(VpsPlan::class, 'group_id')->where('status', 'active');
    }

    // === Helpers ===

    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Auto-generate slug from name
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = \Illuminate\Support\Str::slug($group->name);
                // Đảm bảo unique
                $base = $group->slug;
                $i = 1;
                while (static::where('slug', $group->slug)->exists()) {
                    $group->slug = $base . '-' . $i++;
                }
            }
        });
    }
}
