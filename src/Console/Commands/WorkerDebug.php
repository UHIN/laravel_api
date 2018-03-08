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
    protected $description = 'Allows you to debug a worker.';

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
       if($this->argument('name')=='') {
           $name = $this->ask('What worker would you like to debug?');
       } else {
           $name = $this->argument('name');
       }

        $class = "App\Workers\\".$name;

        $worker = new $class;

        $this->warn('Press Ctrl-C to end debugging session.');

        $worker->run();

    }
}
