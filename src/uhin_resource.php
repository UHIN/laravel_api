<?php

namespace uhin\laravel_api;

use Illuminate\Http\Resources\Json\Resource;

class uhin_resource extends Resource
{

    private $type;

    public function __construct($model, $type)
    {
        $this->type = $type;

        parent::__construct($model);
    }


    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $return = $this->resource->toArray();
        $return['type'] = $this->type;
        return $return;


    }
}
