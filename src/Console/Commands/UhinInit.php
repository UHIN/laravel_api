<?php

namespace uhin\laravel_api\Commands;

use Illuminate\Console\Command;

class UhinInit extends Command
{
    use BaseCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializes the UHIN framework and strips out Users, Auth, and Web routes';

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->copyConfig();
        $this->info('Config file copied');

        $this->fillEnv();
        $this->info('Environment variables copied');

        $this->removeUsersAndAuth();
        $this->info('Users and authentication stripped out');

        $this->removeWebRoutes();
        $this->info('Web routes removed');
    }

    private function copyConfig()
    {
        $stub = __DIR__ . '/../../config/uhin.php';
        $destination = config_path('uhin.php');
        $this->copyStub($stub, $destination);
    }

    private function copyApiRoutes()
    {
        $stub = __DIR__ . '/../../config/uhin.php';
        $destination = config_path('uhin.php');
        $this->copyStub($stub, $destination);
    }

    private function fillEnv()
    {
        $env = base_path('.env');

        // PagerDuty environment variables
        file_put_contents($env, PHP_EOL . "PAGER_DUTY_API_KEY=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "PAGER_DUTY_INTEGRATION_KEY=" . PHP_EOL, FILE_APPEND | LOCK_EX);

        // RabbitMQ environment variables
        file_put_contents($env, PHP_EOL . "RABBITMQ_HOST=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBITMQ_PORT=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBITMQ_USERNAME=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBITMQ_PASSWORD=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBITMQ_EXCHANGE=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBITMQ_QUEUE=" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function removeUsersAndAuth() {
        $this->deleteFile(database_path('factories/UserFactory.php'));
        $this->deleteFiles(database_path('migrations'));
        $this->deleteFile(app_path('User.php'));
        $this->deleteDirectory(app_path('Http/Controllers/Auth'));
    }

    private function removeWebRoutes() {
        $this->deleteFile(base_path('routes/web.php'));

        // Remove the web routes from the provider
        $provider = app_path('Providers/RouteServiceProvider.php');
        $contents = file_get_contents($provider);
        $contents = str_replace('$this->mapWebRoutes();', '', $contents);
        $contents = str_replace("Route::prefix('api')", '', $contents);
        $contents = str_replace("->middleware('api')", "Route::middleware('api')", $contents);
        file_put_contents($provider, $contents);

        // Remove the old api routes and copy the new one
        $stub = __DIR__ . '/stubs/api-routes.stub';
        $destination = base_path('routes/api.php');
        $this->deleteFile($destination);
        $this->copyStub($stub, $destination);
    }
}
