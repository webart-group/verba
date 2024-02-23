<?php

namespace Verba\ObjectType\Attribute\Predefined;

class Collection
{

    protected $items = array();
    protected $A;

    function __construct($A)
    {
        $this->A = $A;
    }

    function add($data)
    {
        $this->items[$data['ot_id']] = new Set($data, $this);
    }

    function get($ot_id = false): Set
    {
        if (!$ot_id) {
            return $this->items;
        }

        $ants = $this->A->oh()->getAncestors();
        array_push($ants, $this->A->oh()->getID());
        $ants = array_reverse($ants);
        foreach ($ants as $cot_id) {
            if (array_key_exists($cot_id, $this->items)) {
                return $this->items[$cot_id];
            }
        }

        $this->items[$ot_id] = Set::createDummy($this);

        return $this->items[$ot_id];
    }
}