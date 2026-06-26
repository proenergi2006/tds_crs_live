<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('uat')) {
            URL::forceScheme('https');
        }

        if ($this->app->environment('local', 'development') && config('mail.dev_redirect')) {
            Mail::alwaysTo(config('mail.dev_redirect'));
        }
    }
}
