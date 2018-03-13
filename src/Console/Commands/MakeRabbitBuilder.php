<?php

namespace uhin\laravel_api\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeRabbitBuilder extends GeneratorCommand
{

    protected $type = 'RabbitBuilder';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uhin:make:rabbit-builder {name}';

    /**
     *php The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Rabbit exchange/queue builder';


    protected function getStub()
    {
        return __DIR__ . '/stubs/rabbit-builder.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Rabbit';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $return = parent::handle();

        $this->info('Make sure your ENV settings are configured and you can now configure your ' . $this->getNameInput() . ' to your liking');
        return $return;
    }
}
