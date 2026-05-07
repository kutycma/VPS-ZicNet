<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $adminName = env('ADMIN_NAME', 'Administrator');
        $adminEmail = env('ADMIN_EMAIL', 'admin@zicnet.vn');
        $adminPassword = env('ADMIN_PASSWORD', 'admin@2026');
        // Tạo tài khoản Admin
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'balance' => 0,
                'status' => 'active',
            ]
        );

        // Settings mặc định
        $settings = [
            ['key' => 'site_name', 'value' => 'VPS ZicNet', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'Hệ thống VPS Reseller tự động', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => '/images/logo.png', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'support@zicnet.vn', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => 'đ', 'group' => 'general'],
            ['key' => 'min_deposit', 'value' => '50000', 'group' => 'billing'],
            ['key' => 'max_deposit', 'value' => '50000000', 'group' => 'billing'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->call(ThemeSeeder::class);
    }
}
