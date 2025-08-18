<?php

namespace uhin\laravel_api\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

        $this->modifyBootstrapFile();
        $this->info('Bootstrap file modified to include API routes');

        return 0;
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
        $this->deleteFile($destination);
        $this->copyStub($stub, $destination);
    }

    private function fillEnv()
    {
        $env = base_path('.env');
        
        $envVariables = [
            'PAGER_DUTY_API_KEY=',
            'PAGER_DUTY_INTEGRATION_KEY=',
            'RABBIT_HOST=',
            'RABBIT_PORT=',
            'RABBIT_USERNAME=',
            'RABBIT_PASSWORD=',
            'RABBIT_SSL=',
            'RABBIT_EXCHANGE=',
            'RABBIT_ROUTING_KEY=',
            'RABBIT_QUEUE=',
        ];

        foreach ($envVariables as $variable) {
            file_put_contents($env, PHP_EOL . $variable, FILE_APPEND | LOCK_EX);
        }
    }

    private function removeUsersAndAuth()
    {
        $this->deleteFile(database_path('factories/UserFactory.php'));
        $this->deleteFiles(database_path('migrations'));
        $this->deleteFile(app_path('Models/User.php'));
        $this->deleteDirectory(app_path('Http/Controllers/Auth'));
        // put an empty file here so that the folder will be pushed to git even if no factories are created
        file_put_contents(database_path('factories/.gitignore'), '');
        // put an empty file here so that the folder will be pushed to git even if no migrations are created
        file_put_contents(database_path('migrations/.gitignore'), '');

        // Remove the HasApiTokens trait from the User model if it exists
        $userModelPath = app_path('Models/User.php');
        if (File::exists($userModelPath)) {
            $contents = File::get($userModelPath);
            $contents = str_replace('use Laravel\Sanctum\HasApiTokens;', '', $contents);
            $contents = str_replace('HasApiTokens, ', '', $contents);
            File::put($userModelPath, $contents);
        }
    }

    private function removeWebRoutes()
    {
        $this->deleteFile(base_path('routes/web.php'));
        touch(base_path('routes/web.php'));

        // Since the RouteServiceProvider has changed in Laravel 12,
        // we will clear out the default routes entirely.
        $provider = app_path('Providers/RouteServiceProvider.php');
        if (File::exists($provider)) {
            $contents = File::get($provider);
            $contents = str_replace([
                'use Illuminate\Support\Facades\Route;',
                'Route::middleware(\'web\')->group(base_path(\'routes/web.php\'));',
                'Route::prefix(\'api\')->middleware(\'api\')->group(base_path(\'routes/api.php\'));'
            ], [
                'use Illuminate\Support\Facades\Route;',
                '',
                ''
            ], $contents);
            File::put($provider, $contents);
        }

        // Remove the old api routes and copy the new one
        $stub = __DIR__ . '/stubs/api-routes.stub';
        $destination = base_path('routes/api.php');
        $this->deleteFile($destination);
        $this->copyStub($stub, $destination);
        
        // As the middleware group is not present, we can safely remove this logic
        $httpKernel = app_path('Http/Kernel.php');
        if (File::exists($httpKernel)) {
            $contents = File::get($httpKernel);
            $contents = preg_replace('/(\$middlewareGroups.*?\\\'api\\\'.*?\[.*?)(\\\'throttle.*?\\\')(.*?])/s', '${1}// ${2}${3}', $contents);
            File::put($httpKernel, $contents);
        }
    }
    
    private function modifyBootstrapFile()
    {
        $bootstrapFile = base_path('bootstrap/app.php');
        if (!File::exists($bootstrapFile)) {
            return;
        }

        $content = File::get($bootstrapFile);
        $routeAddition = '        then: function () {
            Route::middleware(\'api\')
                ->namespace(\'uhin\laravel_api\Controllers\')
                ->group(base_path(\'routes/api.php\'));
        },';

        $pattern = '/->withRouting\((.*?)\)\n/s';

        if (preg_match($pattern, $content, $matches)) {
            $existingRouting = trim($matches[1]);
            if (!Str::contains($existingRouting, 'then:')) {
                $newRouting = $existingRouting . ",\n" . $routeAddition;
                $content = str_replace($existingRouting, $newRouting, $content);
            }
        }

        File::put($bootstrapFile, $content);
    }
}