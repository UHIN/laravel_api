<?php

namespace uhin\laravel_api\Console\Commands;

use Illuminate\Console\Command;

class WorkerStop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:workers:stop';

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
        exec("pkill -f 'php -r include'");

        return 0;
    }
}
