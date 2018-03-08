<?php

namespace uhin\laravel_api\Providers;

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
            __DIR__ . '/../config/uhin.php' => config_path('uhin.php'),
        ], 'uhin');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            \uhin\laravel_api\Commands\WorkerStart::class,
            \uhin\laravel_api\Commands\WorkerStop::class,
            \uhin\laravel_api\Commands\MakeWorker::class,
            \uhin\laravel_api\Commands\WorkerDebug::class
        ]);
    }
}
