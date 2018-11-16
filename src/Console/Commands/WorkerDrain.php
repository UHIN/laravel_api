<?php

namespace uhin\laravel_api\Commands;

use Illuminate\Console\Command;

class WorkerDrain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:workers:drain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allows running workers to complete the message in progress but not start another.';

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
        file_put_contents(
            storage_path('framework/drain'),
            json_encode($this->getDownFilePayload(), JSON_PRETTY_PRINT)
        );

        $this->comment('Workers are draining...');
    }

    /**
     * Get the payload to be placed in the "down" file.
     *
     * @return array
     */
    protected function getDownFilePayload()
    {
        return [
            'time' => $this->currentTime(),
        ];
    }
}
