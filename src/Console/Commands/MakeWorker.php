<?php

namespace uhin\laravel_api\Commands;

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
        return __DIR__ . '/stubs/worker.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Workers';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');
        $name = $this->argument('name');

        switch($type) {
            case "rabbit":
                break;
            default:
                echo "That type of worker is not currently implemented.";
                exit();
        }

        parent::handle();

        $this->warn( 'In app/Kernel.php file make sure the schedule() function contains:  $schedule->command(\'worker:start\')->everyMinute();');


    }
}
