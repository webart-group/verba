<?php

namespace Mod;

class Textblock extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $otic_ot = 'textblock';

    function issetFieldValue($oh, $field, $value)
    {
        $field = (string)$field;
        $value = (string)$value;
        $oh = \Verba\_oh($oh);
        $qm = new \Verba\QueryMaker($oh->getID(), $oh->getBaseKey(), array($field));
        $qm->addWhere("`$field` = '" . $this->DB()->escape_string(trim($value)) . "'");
        $qm->addLimit(1);
        $qm->makeQuery();
        $oRes = $this->DB()->query($qm->getQuery());

        return ($oRes->getNumRows() == 1);
    }

}
