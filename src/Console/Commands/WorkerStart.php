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
    protected $signature = 'worker:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $workers_path = app_path('Workers/').'*.php';

        foreach(glob($workers_path) as $filename)
        {
            echo $filename;
            //include_once app_path('Workers/').$filename;

            //$class = basename($filename);

            //$worker = new $class;

            //$worker::start($class);

        }
    }
}
