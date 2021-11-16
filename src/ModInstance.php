<?php

namespace Verba;


trait ModInstance
{
    /**
     * @var \Mod
     */
    protected static $_instance = null;

    public static function getClassName() {
        return get_class();
    }

    public static function getInstance($className = null) {

        if(!is_string($className)){
            $className = static::getClassName();
        }

        if (self::$_instance === null) {
            self::$_instance = new $className();
            self::$_instance->init();
        }else{
            if(get_class(self::$_instance) != $className){
                self::$_instance = false;
            }
        }

        return self::$_instance;
    }

    public static function i() {
        return self::getInstance();
    }
}
