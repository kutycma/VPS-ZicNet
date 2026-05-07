<?php

namespace App\Services\Setting;

use App\Models\Setting;

class SystemSettingService
{
    /**
     * Lấy giá trị setting
     */
    public function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Set giá trị setting
     */
    public function set(string $key, $value, string $group = 'general')
    {
        return Setting::set($key, $value, $group);
    }

    /**
     * Lấy tất cả settings theo group
     */
    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    /**
     * Cập nhật nhiều settings cùng lúc
     */
    public function updateMany(array $settings, string $group = 'general')
    {
        foreach ($settings as $key => $value) {
            Setting::set($key, $value, $group);
        }
    }

    /**
     * Lấy tất cả settings dạng key-value
     */
    public function all(): array
    {
        return Setting::all()->pluck('value', 'key')->toArray();
    }
}
