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

        if (! $this->app->routesAreCached()) 
        {
            require __DIR__.'/../routes/shc.php';
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            \uhin\laravel_api\Commands\MakeEndpoint::class,
            \uhin\laravel_api\Commands\MakeRabbitBuilder::class,
            \uhin\laravel_api\Commands\MakeWorker::class,
            \uhin\laravel_api\Commands\WorkerDebug::class,
            \uhin\laravel_api\Commands\WorkerStart::class,
            \uhin\laravel_api\Commands\WorkerStop::class,
            \uhin\laravel_api\Commands\UhinInit::class,
        ]);

    }
}
