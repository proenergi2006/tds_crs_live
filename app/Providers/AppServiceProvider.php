<?php

namespace App\Providers;

use App\Models\Personnel;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (app()->environment('uat')) {
            URL::forceScheme('https');
        }

        if ($this->app->environment('local', 'development') && config('mail.dev_redirect')) {
            Mail::alwaysTo(config('mail.dev_redirect'));
        }

        Relation::morphMap([
            'transporter' => Transporter::class,
            'personnel' => Personnel::class,
            'vessel' => Vessel::class,
            'truck' => Truck::class,
        ]);
    }
}
