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

        $bootCode = "(new \\App\\Rabbit\\" . $this->getNameInput() . ")->execute();";

        $provider = base_path('app/Providers/AppServiceProvider.php');
        $contents = file_get_contents($provider);
        if (!str_contains($contents, $bootCode)) {
            $contents = preg_replace('/(function\s+boot.*?\{)(.*?)(\})/s', '${1}${2}' . PHP_EOL . '        ' . $bootCode . PHP_EOL . '    ${3}', $contents);
            file_put_contents($provider, $contents);
        }

        $this->info('Make sure your ENV settings are configured. You can now configure your ' . $this->getNameInput() . ' to your liking');
        return $return;
    }
}
