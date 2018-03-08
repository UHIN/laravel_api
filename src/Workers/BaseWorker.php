<?php

namespace uhin\laravel_api\Workers;


abstract class BaseWorker
{
    protected $lockfile;

    abstract public  function run();

    abstract public  function start($pidName);
}