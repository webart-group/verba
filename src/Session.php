<?php

namespace Verba;

/**
 * Class Session
 * @package Verba
 */
class Session
{

    protected $storage;

    function __construct()
    {
        $this->initStorage();
    }

    function initStorage(){
        $this->storage = &$_SESSION;
    }

    function getStorage()
    {
        return $this->storage;
    }

    function get($key)
    {
        return array_key_exists($key, $this->storage)
            ? $this->storage[$key]
            : null;
    }

    function store($key, $value = null)
    {
        $this->storage[$key] = $value;
    }

    function remove($key)
    {
        if(array_key_exists($key, $this->storage)){
            unset($this->storage[$key]);
        }
        return $this;
    }

    function clear()
    {
        $this->storage = [];
        return $this;
    }
}
