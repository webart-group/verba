<?php

namespace Verba;

/**
 * Class Session
 * @package Verba
 */
class Session
{

    protected $storage;

    function initStorage(){
        $this->storage = &$_SESSION;
        return $this->storage;
    }

    function getStorage()
    {
        return $this->storage === null
            ? $this->initStorage()
            : $this->storage;
    }

    function get($key)
    {
        return array_key_exists($key, $this->getStorage())
            ? $this->storage[$key]
            : null;
    }

    function store($key, $value = null)
    {
        $this->getStorage();
        $this->storage[$key] = $value;
    }

    function remove($key)
    {
        $this->getStorage();
        if(array_key_exists($key, $this->storage)){
            unset($this->storage[$key]);
        }
        return $this;
    }

    function clear()
    {
        $this->getStorage();
        $this->storage = [];
        return $this;
    }
}
