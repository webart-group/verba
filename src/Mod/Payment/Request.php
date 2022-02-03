<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 21:09
 */

namespace Mod\Payment;


class Request extends \Verba\Configurable {

    protected $_confPropName = 'fields';

    protected $fields = array();

    protected $Notify;

    function __construct($Notify, $ct)
    {

        $this->Notify = $Notify;

        $cfg = $this->extractRequestFields($ct);

        $this->applyConfigDirect($cfg);

    }

    function __get($propName){
        return array_key_exists($propName, $this->fields) ? $this->fields[$propName] : null;
    }

    function extractRequestFields($ct){

        if(!is_array($ct) || !count($ct)){
            return false;
        }

        return $ct;
    }

    function exportAsSerialized(){
        return count($this->fields) ? serialize($this->fields) : '';
    }

    function getFields(){
        return $this->fields;
    }
}