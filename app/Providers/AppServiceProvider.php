<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        // Dynamically set SMTP configuration if available
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $mailHost = \App\Models\Setting::get('mail_host');
                if ($mailHost) {
                    $config = [
                        'transport' => 'smtp',
                        'host'       => $mailHost,
                        'port'       => \App\Models\Setting::get('mail_port', '465'),
                        'encryption' => \App\Models\Setting::get('mail_encryption', 'tls'),
                        'username'   => \App\Models\Setting::get('mail_username'),
                        'password'   => \App\Models\Setting::get('mail_password'),
                        'timeout'    => null,
                        'auth_mode'  => null,
                    ];
                    \Illuminate\Support\Facades\Config::set('mail.mailers.smtp', $config);
                    
                    \Illuminate\Support\Facades\Config::set('mail.from.address', \App\Models\Setting::get('mail_username'));
                    \Illuminate\Support\Facades\Config::set('mail.from.name', \App\Models\Setting::get('mail_from_name', 'VPS ZicNet'));
                }
            }
        } catch (\Exception $e) {
            // Ignore if DB not ready
        }
    }
}
