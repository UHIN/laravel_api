<?php

namespace uhin\laravel_api\Workers;


use Exception;

abstract class BaseWorker
{

    public final function __construct() {
        if(!isset($this->numberOfWorkers))
            throw new Exception(get_class($this) . ' must have a $numberOfWorkers defined. ');
    }

    abstract public static function run();

    abstract public static function start($pidName);
}