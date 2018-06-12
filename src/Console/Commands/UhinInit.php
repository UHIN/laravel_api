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

        $this->copyHandler();
        $this->info('Exception file copied');

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

    private function copyHandler()
    {
        $stub = __DIR__ . '/../../Helpers/Handler.php';
        $destination = app_path('Exceptions/Handler.php');
        $this->copyStub($stub, $destination);
    }

    private function fillEnv()
    {
        $env = base_path('.env');

        // PagerDuty environment variables
        file_put_contents($env, PHP_EOL . "PAGER_DUTY_API_KEY=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "PAGER_DUTY_INTEGRATION_KEY=" . PHP_EOL, FILE_APPEND | LOCK_EX);

        // RabbitMQ environment variables
        file_put_contents($env, PHP_EOL . "RABBIT_HOST=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_PORT=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_USERNAME=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_PASSWORD=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_EXCHANGE=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_ROUTING_KEY=" . PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($env, "RABBIT_QUEUE=" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function removeUsersAndAuth() {
        $this->deleteFile(database_path('factories/UserFactory.php'));
        $this->deleteFiles(database_path('migrations'));
        $this->deleteFile(app_path('User.php'));
        $this->deleteDirectory(app_path('Http/Controllers/Auth'));
        // put an empty file here so that the folder will be pushed to git even if no factories are created
        file_put_contents(database_path('factories/.gitignore'), '');
        // put an empty file here so that the folder will be pushed to git even if no migrations are created
        file_put_contents(database_path('migrations/.gitignore'), '');
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

        // Remove the API throttling setting
        $httpKernel = app_path('Http/Kernel.php');
        $contents = file_get_contents($httpKernel);
        $contents = preg_replace('/(\$middlewareGroups.*?\\\'api\\\'.*?\[.*?)(\\\'throttle.*?\\\')(.*?])/s', '${1}// ${2}${3}', $contents);
        file_put_contents($httpKernel, $contents);
    }
}
