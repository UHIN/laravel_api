<?php

namespace uhin\laravel_api\Commands;

use Illuminate\Console\Command;

class WorkerStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:worker:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start all workers in the app/Workers folder';

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

        $workers_path = app_path('Workers/');

        if (!file_exists($workers_path)) {
            mkdir($workers_path, 0777, true);
        }

        foreach(glob($workers_path.'*.php') as $filename)
        {

            include $filename;

            $class = "\App\Workers\\".basename($filename, ".php");

            $worker = new $class;

            $worker->start(basename($filename, ".php"));

            //$worker->run(basename($filename, ".php"));
        }
    }
}
