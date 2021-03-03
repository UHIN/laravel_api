<?php

namespace uhin\laravel_api\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;


class MakeWorker extends GeneratorCommand
{

    protected $type = 'Worker';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:make:worker {name} {--t|type=rabbit}';

    /**
     *php The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new worker class';


    protected function getStub()
    {
        $type = $this->option('type');
        switch ($type) {
            case "database":
                return __DIR__ . '/stubs/worker-database.stub';
                break;
            case "rabbit":
                return __DIR__ . '/stubs/worker-rabbit.stub';
                break;
            default:
                echo "That type of worker is not currently implemented.";
                exit();
        }
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Workers';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        $this->warn('In app/Console/Kernel.php file make sure the schedule() function contains:  $schedule->command(\'uhin:workers:start\')->everyMinute();');
    }

}
