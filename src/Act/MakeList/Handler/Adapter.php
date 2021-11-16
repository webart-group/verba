<?php
namespace Verba\Act\MakeList\Handler;

use Act\MakeList\Handler;

class Adapter extends Handler {

    protected $Handler = false;

    function __construct($list, $cfg) {
        parent::__construct($list, $cfg);
    }

    public static function create($className, $list, $cfg) {

        if(!$className instanceof \ObjectType\Attribute\Handler) {
            $adapterName = __NAMESPACE__.'\Adapter\AttributeValue';
        }else{
            $adapterName = __NAMESPACE__.'\Adapter';
        }
        /**
         * @var $Adapter Adapter
         */
        $Adapter = new $adapterName($list, $cfg);
        $Adapter->initHandler($className, $cfg);

        return $Adapter;
    }

    function initHandler($className, $cfg){
        if(!class_exists($className)) {
            return false;
        }
        $this->Handler = new $className($this->list, $cfg);
        return $this->Handler;
    }

    function run()
    {
        if(!is_object($this->Handler) || !is_callable(array($this->Handler, 'run')))
        {
            $this->log()->error('Bad list handler');
            return false;
        }

        return $this->Handler->run();
    }
}
