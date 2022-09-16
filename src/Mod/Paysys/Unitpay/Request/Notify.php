<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:55
 */

namespace Verba\Mod\Paysys\Unitpay\Request;


class Notify extends \Verba\Mod\Payment\Request\Notify {

    function isValid(){
        return isset($this->fields['params'])
            && is_array($this->fields['params'])
            && array_key_exists('signature', $this->fields['params'])
            && array_key_exists('orderCurrency', $this->fields['params'])
            && isset($this->fields['method']);
    }

}

