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
        $this->info('Running Laravel\'s official API installation command...');
        $this->call('install:api', ['--without-migration-prompt' => true]);

        $this->removeSanctum();
        $this->info('Sanctum has been removed');

        $this->copyConfig();
        $this->info('Config file copied');

        $this->copyHandler();
        $this->info('Exception file copied');

        $this->fillEnv();
        $this->info('Environment variables copied');

        $this->removeWebRoutesAndFrontendAssets();
        $this->info('Web routes and front-end assets removed');

        $this->removeDatabaseFolders();
        $this->info('Database factories and seeders removed');

        $this->cleanupBootstrapFile();
        $this->info('Cleaned up bootstrap file.');

        return 0;
    }

    private function removeSanctum()
    {
        $this->info('Manually clearing service provider, packages and config caches...');
        $servicesCacheFile = base_path('bootstrap/cache/services.php');
        if (File::exists($servicesCacheFile)) {
            File::delete($servicesCacheFile);
        }
        $configCacheFile = base_path('bootstrap/cache/config.php');
        if (File::exists($configCacheFile)) {
            File::delete($configCacheFile);
        }
        $packagesCacheFile = base_path('bootstrap/cache/packages.php');
        if (File::exists($packagesCacheFile)) {
            File::delete($packagesCacheFile);
        }

        $this->info('Removing laravel/sanctum package from composer.json...');
        $composerFile = base_path('composer.json');
        if (File::exists($composerFile)) {
            $content = File::get($composerFile);
            $content = preg_replace('/"laravel\/sanctum":.*?,/s', '', $content);
            File::put($composerFile, $content);
        }

        $this->info('Running composer update to clean up Sanctum files...');
        exec("composer update");

        $routesFile = base_path('routes/api.php');
        if (File::exists($routesFile)) {
            $content = File::get($routesFile);
            $pattern = '/Route::get\(\'\/user\', function \(Request \$request\) \{\s*return \$request->user\(\);\s*\}\)->middleware\(\'auth:sanctum\'\);/s';
            $content = preg_replace($pattern, '', $content);
            File::put($routesFile, $content);
        }

        $this->info('Removing sanctum config file...');
        File::delete(config_path('sanctum.php'));

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

    private function removeWebRoutesAndFrontendAssets()
    {
        $this->deleteFile(base_path('routes/web.php'));
        $this->deleteDirectory(base_path('resources'));
        $this->deleteFile(public_path('favicon.ico'));
        $this->deleteFile(public_path('mix-manifest.json'));
        $this->deleteDirectory(public_path('build'));
        $this->deleteFile(base_path('package.json'));
        $this->deleteFile(base_path('vite.config.js'));
    }

    private function removeDatabaseFolders()
    {
        $this->deleteFile(app_path('Models/User.php'));
        $this->deleteFiles(database_path('migrations'));
        $this->deleteDirectory(database_path('factories'));
        $this->deleteDirectory(database_path('seeders'));
    }

    private function cleanupBootstrapFile()
    {
        $bootstrapFile = base_path('bootstrap/app.php');
        if (!File::exists($bootstrapFile)) {
            return;
        }

        $content = File::get($bootstrapFile);
        
        $pattern = '/web: __DIR__.*?routes\/web\.php\',/s';
        $content = preg_replace($pattern, '', $content);
        
        File::put($bootstrapFile, $content);
    }
}