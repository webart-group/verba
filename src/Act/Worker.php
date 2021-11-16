<?php
namespace Verba\Act;

class Worker extends \Verba\Configurable {

    protected $_confCreatePropOnFly = true;
    protected $_confOnlyPubPropDirectSetAllowed = false;
    /**
     * @var \Verba\Act\Action
     */
    protected $parent;
    /**
     * @var \Verba\Act\Action
     */
    protected $ah;
    protected $_className;
    protected $_alias;

    protected $jsScriptFile;

    /**
    * @param \Verba\Act\MakeList|\Act\Form $list
    * @param mixed $cfg
    */
    function __construct($parent, $cfg = null)
    {
        $this->ah = $this->parent = $parent;
        $this->applyConfigDirect($cfg);
        $this->init();
    }

    function init() {

    }

    function setAlias($alias) {
        if(!$alias || !is_string($alias)) {
            return $this->_alias;
        }
        $this->_alias = preg_replace("/[^a-z09]/i",'_', $alias);

        return $this->_alias;
    }

    function getAlias() {
        return $this->_alias;
    }

    function getJsScriptFile() {
        return $this->jsScriptFile;
    }

    function setClassName($val) {
        if(strpos($val,'\\') !== false) {
            $classPath = explode('\\', $val);
            $this->_className = $classPath[count($classPath) - 1];
        } else {
            $this->_className = $val;
        }
        return $this->_className;
    }

    function getClassName() {
        return $this->_className;
    }

    function parseClientCfg() {
        $r = array (
            '_className' => $this->_className,
        );

        $properties = \Verba\get_object_vars_public($this);

        foreach ($properties as $property => $propType) {
            $r[$property] = $this->{$property};
        }

        return $r;
    }
}
