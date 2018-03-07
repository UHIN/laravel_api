<?php

namespace uhin\laravel_api;

use Illuminate\Support\ServiceProvider;

class UhinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'config/uhin.php' => config_path('uhin.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
