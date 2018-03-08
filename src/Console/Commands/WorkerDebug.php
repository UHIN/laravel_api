<?php

namespace uhin\laravel_api\Commands;

use Illuminate\Console\Command;

class WorkerDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:debug:worker {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop all workers in the app/Workers folder';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');


        $class = '\App\Workers\$name';

        $worker = new $class;

        $worker->run();

    }
}
