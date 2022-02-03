<?php

namespace Mod\Game\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class QuantityAvaible extends Field
{
    function run(){

        if(is_numeric($this->list->row['quantityAvaible']) && $this->list->row['quantityAvaible'] > 0){
            return $this->list->row['quantityAvaible'];
        }

        return '';
    }
}
