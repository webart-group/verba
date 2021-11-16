<?php
namespace Verba;

class Eventer extends Base
{

    protected $_events = array();

    function listen($event, $method, $object, $alias = null)
    {

        if (!array_key_exists($event, $this->_events)) {
            $this->_events[$event] = array();
        }
        $alias = $alias ? (string)$alias : 'EH-' . rand();
        $r = array($object, $method);
        $args = func_get_args();
        if (count($args) > 4) {
            $args = array_slice($args, 4, null, true);
            $r[] = $args;
        }
        // prepend
//        if (false) {
//            $this->_events[$event] = [$alias => $r] + $this->_events[$event];
//        } else {
        $this->_events[$event][$alias] = $r;
        //}

        return $alias;
    }

    function listenPrepend($event, $method, $object, $alias = null)
    {
        return $this->listen($event, $method, $object, $alias);
    }

    function isListen($event, $alias)
    {
        return isset($this->_events[$event][$alias]);
    }

    function unlisten($event, $alias)
    {

        if ($alias === false || $alias === null && isset($this->_events[$event])) {
            unset($this->_events[$event]);
        } elseif (isset($this->_events[$event][$alias])) {
            unset($this->_events[$event][$alias]);
        } else {
            return false;
        }

        return true;
    }

    function fire($event)
    {
        if (!array_key_exists($event, $this->_events) || !count($this->_events[$event])) {
            return false;
        }

        foreach ($this->_events[$event] as $alias => $h) {
            if (!is_object($h[0]) || !is_callable(array($h[0], $h[1]))) {
                continue;
            }
            $r = call_user_func_array(array($h[0], $h[1]), isset($h[2]) ? $h[2] : array());
        }
    }
}
