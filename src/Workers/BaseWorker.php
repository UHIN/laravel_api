<?php

namespace uhin\laravel_api\Workers;


abstract class BaseWorker
{

    abstract public  function run($name);

    abstract public  function start($pidName);
}