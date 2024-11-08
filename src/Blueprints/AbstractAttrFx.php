<?php

namespace Verba\Blueprints;

abstract class AbstractAttrFx
{
    protected $attr_id;
    protected $ot_id;
    protected $db;

    public function __construct($tableInterface, $attr_id, $ot_id)
    {
        $this->attr_id = $attr_id;
        $this->ot_id = $ot_id;
        $this->db = $tableInterface;
    }
}
