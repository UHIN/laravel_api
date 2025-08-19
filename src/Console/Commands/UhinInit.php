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

        $this->removeWebRoutesAndFrontendAssets();
        $this->info('Web routes and front-end assets removed');

        $this->removeDatabaseFolders();
        $this->info('Database factories and seeders removed');

        $this->modifyBootstrapFile();
        $this->info('Bootstrap file modified to include API routes');

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

    private function removeWebRoutesAndFrontendAssets()
    {
        $this->deleteFile(base_path('routes/web.php'));
        $this->deleteDirectory(base_path('resources'));
        $this->deleteFile(public_path('favicon.ico'));
        $this->deleteFile(public_path('mix-manifest.json'));
        $this->deleteDirectory(public_path('build'));
    }

    private function removeDatabaseFolders()
    {
        $this->deleteFile(database_path('factories/UserFactory.php'));
        $this->deleteFiles(database_path('migrations'));
        $this->deleteDirectory(database_path('factories'));
        $this->deleteDirectory(database_path('seeders'));
    }
    
    private function modifyBootstrapFile()
    {
        $bootstrapFile = base_path('bootstrap/app.php');
        if (!File::exists($bootstrapFile)) {
            return;
        }

        $content = File::get($bootstrapFile);
        
        if (!Str::contains($content, 'use Illuminate\Support\Facades\Route;')) {
            $content = str_replace('use Illuminate\Foundation\Application;', "use Illuminate\Foundation\Application;\nuse Illuminate\Support\Facades\Route;", $content);
        }

        $routeAddition = '        then: function () {
            Route::middleware(\'api\')
                ->namespace(\'uhin\laravel_api\Controllers\')
                ->group(base_path(\'routes/api.php\'));
        },';

        $pattern = '/->withRouting\((.*?)\s*\)\s*->(withMiddleware|withExceptions|create)/s';

        if (preg_match($pattern, $content, $matches)) {
            $existingRouting = $matches[1];
            $nextMethod = $matches[2];

            $existingRouting = preg_replace('/^.*?web: __DIR__.*?,\s*/s', '', $existingRouting);
            
            $trimmedRouting = trim($existingRouting, " \n\r\t,");

            if (!Str::contains($trimmedRouting, 'uhin\laravel_api\Controllers')) {
                if (!empty($trimmedRouting)) {
                    $trimmedRouting .= ",";
                }

                $newRoutingBlock = "->withRouting(\n" . $trimmedRouting . "\n" . $routeAddition . "\n    )->" . $nextMethod;

                $content = str_replace($matches[0], $newRoutingBlock, $content);
                File::put($bootstrapFile, $content);
            }
        }

        // Add new api Routes file
        $stub = __DIR__ . '/stubs/api-routes.stub';
        $destination = base_path('routes/api.php');
        $this->deleteFile($destination);
        $this->copyStub($stub, $destination);
    }
}