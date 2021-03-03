<?php

namespace uhin\laravel_api\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeEndpoint extends GeneratorCommand
{
    use BaseCommand;

    private $stub = null;
    private $namespace = null;
    private $nameInput = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:make:endpoint {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . $this->namespace;
    }

    protected function getStub()
    {
        return $this->stub;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $model = $this->argument('name');

        // Model
        $this->call('make:model', [
            'name' => "{$model}",
        ]);

        // Migration
        $this->call('make:migration', [
            'name' => "create_" . Str::snake(Str::plural($model)) . "_table",
        ]);

        // Factory
        $this->call('make:factory', [
            'name' => "{$model}Factory",
            '--model' => "{$model}",
        ]);

        // Seeder
        $this->call('make:seed', [
            'name' => Str::plural($model) . "TableSeeder",
        ]);
        // Make a call to the new seeder in the DatabaseSeeder.php file
        $seederCall = '$this->call(' . Str::plural($model) . 'TableSeeder::class);';
        $seeder = database_path('seeders/DatabaseSeeder.php');
        $contents = file_get_contents($seeder);
        if (!Str::contains($contents, $seederCall)) {
            $contents = preg_replace('/(function\s+run.*?\{)(.*?)(\})/s', '${1}${2}' . PHP_EOL . '        ' . $seederCall . PHP_EOL . '    ${3}', $contents);
            file_put_contents($seeder, $contents);
        }

        // Resource
        $this->stub = __DIR__ . '/stubs/resource.stub';
        $this->namespace = '\Http\Resources';
        $this->type = 'Resource';
        $this->nameInput = $model . 'Resource';
        parent::handle();

        // Controller
        $this->stub = __DIR__ . '/stubs/controller.stub';
        $this->namespace = '\Http\Controllers';
        $this->type = 'Controller';
        $this->nameInput = $model . 'Controller';
        parent::handle();

        // Reset the name input
        $this->nameInput = null;

        // Routes
        $destination = base_path('routes/api.php');
        $name = $this->qualifyClass($this->getNameInput());
        $stub = file_get_contents(__DIR__ . '/stubs/endpoint-routes.stub');
        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
        $stub = $this->replaceResourceType($stub, $name);
        file_put_contents($destination, PHP_EOL . $stub . PHP_EOL, FILE_APPEND | LOCK_EX);

        // Inject the import into the routes file.
        $routes = file($destination);

        $index = -1;
        for ($i=0; $i < count($routes) ; $i++) {
            if(substr($routes[$i],0,3) ==="use") {
                $index = $i;
                break;
            }
        }

        if($index > -1) {
            array_splice($routes,$index,0,"use App\\Http\\Controllers\\".$model . 'Controller;'.PHP_EOL);
            file_put_contents($destination, $routes,  LOCK_EX);
        }
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);
        return $this->replaceResourceType($stub, $name);
    }

    /**
     * Replace the resource type in the Resource.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceResourceType($stub, $name)
    {
        $stub = str_replace('DummyTypePlural', str_replace('_', '-', Str::snake(Str::plural($this->argument('name')))), $stub);
        $stub = str_replace('DummyType', str_replace('_', '-', Str::snake($this->argument('name'))), $stub);
        $stub = str_replace('DummyModelVariable', lcfirst(class_basename($this->argument('name'))), $stub);
        $stub = str_replace('DummyModel', $this->argument('name'), $stub);
        return $stub;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        if ($this->nameInput !== null) {
            return $this->nameInput;
        } else {
            return parent::getNameInput();
        }
    }

}
