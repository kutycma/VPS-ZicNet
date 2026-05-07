<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Lấy giá trị setting theo key
     */
    public static function get($key, $default = null)
    {
        try {
            if (!Schema::hasTable((new static())->getTable())) {
                return $default;
            }

            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set giá trị setting
     */
    public static function set($key, $value, $group = 'general')
    {
        try {
            if (!Schema::hasTable((new static())->getTable())) {
                return null;
            }

            return static::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Lấy tất cả settings theo group
     */
    public static function getGroup($group)
    {
        try {
            if (!Schema::hasTable((new static())->getTable())) {
                return [];
            }

            return static::where('group', $group)->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
