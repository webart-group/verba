<?php

namespace Verba\Blueprints;

class Result implements BlueprintServiceResultInterface
{
    public $ot_id;
    public $ot_code;
    public $key_id;
    public $key_code;
    public $vlt_id;
    public $tableName;
    public $prim_attr_id;
    public $attributes;
    public $attributesFinalCfg;

    public function getAttributes() : array
    {
        return $this->attributes;
    }
}
