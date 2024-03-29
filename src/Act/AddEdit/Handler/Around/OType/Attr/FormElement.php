<?php

namespace Verba\Act\AddEdit\Handler\Around\OType\Attr;

use \Verba\Act\AddEdit\Handler\Around;

class FormElement extends Around
{
    function run()
    {
        if($this->action == 'edit'){
            return $this->getExistsValue('form_element');
        }

        if(strpos($this->value, '.') !== false){
            $this->value = explode('.', $this->value)[0];
        }

        switch($this->value) {
            case 'datetimeselector': $this->value = 'datetime'; break;
            case 'paysys': $this->value = 'text'; break;
        }

        $mOtype = \Verba\Mod\Otype::getInstance();
        //list($type, $lenght, $default) = $mOtype->getColumnTypeForAttr();
        $fes = $mOtype->gC('avaibleFormElements');
        if(!array_key_exists($this->value, $fes)){
            $this->log()->error('Unknown Form Element code '.var_export($this->value, true));
            return false;
        }

        return $this->value;
    }
}
