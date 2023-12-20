<?php

namespace App\Providers;

use App\Services\DefaultProfileImage;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot(UrlGenerator $url)
    {
        if (env('APP_ENV') !== 'local') { //so you can work on it locally
            $url->forceScheme('https');
            // \URL::forceScheme('https');
        }
    }
}
