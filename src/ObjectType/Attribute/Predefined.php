<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 11:46
 */

namespace Verba\ObjectType\Attribute;


class Predefined extends \Verba\ObjectType\Attribute
{

    protected $values = null;

    function getValues()
    {

        $PdSet = $this->PdSet();
        if (!$PdSet || !$PdSet->pd_set['id']) {
            return false;
        }

        return $PdSet->getValues();
    }

    function filterValues($filters)
    {
        $PdSet = $this->PdSet();
        if (!$PdSet || !$PdSet->pd_set['id']) {
            return false;
        }

        return $PdSet->filterValues($filters);
    }

}
