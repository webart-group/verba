<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ForeignId extends Around
{
    function run()
    {
        $attr_code = $this->A->getCode();
        $rawValue = $this->ah->getGettedValue($attr_code . '_hidden') !== null
            ? $this->ah->getGettedValue($attr_code . '_hidden')
            : $this->ah->getGettedValue($attr_code);

        if (is_numeric($rawValue)) {
            $foreign_iid = intval($rawValue);
        } elseif (is_string($rawValue) && !empty($rawValue)) {
            $foreign_iid = $rawValue;
        }
        if (!isset($foreign_iid)) {
            if (isset($this->value)) {
                $foreign_iid = $this->value;
            } else {
                return null;
            }
        }
        if (isset($this->params['ot_id']) && $foreign_iid) {
            $linkedOt = \Verba\_oh($this->params['ot_id']);
            $linkedAttr = $linkedOt->A($this->params['field2display'])->getCode();
            $linkedItem = $linkedOt->getData($foreign_iid, 1, array($linkedAttr));
            if (is_array($linkedItem) && isset($linkedItem[$linkedAttr])) {
                $this->ah->addExistsValues(array($this->A->getCode() . '__value' => $linkedItem[$linkedAttr]));
            }
        }

        return $foreign_iid;
    }
}
