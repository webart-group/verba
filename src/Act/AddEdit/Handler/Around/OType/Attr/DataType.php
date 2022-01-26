<?php

namespace Verba\Act\AddEdit\Handler\Around\OType\Attr;

use \Verba\Act\AddEdit\Handler\Around;

class DataType extends Around
{
    protected $_allowedEdit = false;

    function run()
    {
        if($this->action == 'edit'){
            return $this->getExistsValue('data_type');
        }

        $mOtype = \Mod\Otype::getInstance();
        $dts = $mOtype->gC('avaibleDataTypes');
        if(!array_key_exists($this->value, $dts)){
            $this->log()->error('Unknown OAttr Data Type code '.var_export($this->value, true));
            return false;
        }

        return $this->value;
    }
}
