<?php
namespace Verba\Html\Includes;

class InternalPriority {
    protected static $types = array(
        'css' => array(),
        'js' => array()
    ); // array('css' => array(), 'js' => array());

    function getNewValue($type, $priority)
    {

        if (!array_key_exists($type, self::$types)) {
            self::$types[$type] = array();
        }
        if (!array_key_exists($priority, self::$types[$type])) {
            self::$types[$type][$priority] = 10000;
        }
        return --self::$types[$type][$priority];

    }
}