<?php

namespace uhin\laravel_api\Workers;


class BaseRabbitWorker extends BaseWorker
{


    public static function start($pidName) {

        $pid_path = storage_path('pids/'.$pidName.'/');

        if (!file_exists($pid_path)) {
            mkdir($pid_path, 0777, true);
        }







    }

    public static function run() {

    }
}