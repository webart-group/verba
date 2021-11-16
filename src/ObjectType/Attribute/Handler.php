<?php
namespace Verba\ObjectType\Attribute;

class Handler extends \Verba\Configurable
{
    /**
     * @var \Verba\Model
     */
    protected $oh;

    /**
     * @var \ObjectType\Attribute
     */
    protected $A;
    protected $attr_code;

    /**
     * @var \Verba\Act\MakeList|\Act\AddEdit
     */
    protected $ah;

    /**
     * @var \FastTemplate
     */
    protected $tpl;

    public $templates = array();

    public $value;

    /**
     * Constructor
     * @param $oh
     * @param $A string|\ObjectType\Attribute
     * @param bool $cfg
     */
    function __construct($oh, $A, $cfg = false, $ah = null)
    {
        $this->oh = \Verba\_oh($oh);
        $this->A = $this->oh->A($A);
        if($this->A){
            $this->attr_code = $this->A->getCode();
        }else{
            $this->attr_code = $A;
        }

        if(is_object($ah)){
            $this->ah = $ah;
        }

        if (is_array($cfg) && count($cfg)) {
            $this->applyConfigDirect($cfg);
        }

        $this->init();

    }

    function initTpl() {
        $this->tpl = \Verba\Hive::initTpl();
    }

    function init()
    {
        $this->initTpl();
    }

    function run()
    {
        return null;
    }

    /**
     * @param $oh
     * @param $A
     * @param $handlerString
     * @param bool $extra_cfg
     * @return \ObjectType\Attribute\Handler|bool
     */
    static function extractHandlerFromCfg($oh, $A, $handlerString, $extra_cfg = false)
    {

        if (!is_string($handlerString)
            || empty($handlerString)
            || !preg_match("/([a-z_0-9\\\\]+)(?:\((.*)\))?$/i", $handlerString, $_buf)
        ) {
            return false;
        }

        $className = $_buf[1];
        $cfg = array();
        if (isset($_buf[2]) && !empty($_buf[2])) {
            $cfgParts = explode(',', $_buf[2]);
            foreach ($cfgParts as $cfgPair) {
                list($cfgKey, $cfgValue) = explode('=', $cfgPair);
                $cfg[$cfgKey] = $cfgValue;
            }
        }

        if (is_array($extra_cfg) && count($extra_cfg)) {
            $cfg = array_replace_recursive($cfg, $extra_cfg);
        }

        $handler = new $className($oh, $A, $cfg);

        return $handler;
    }

    function oh()
    {
        return $this->oh;
    }

    function getOh()
    {
        return $this->oh();
    }

    function setValue($val)
    {
        $this->value = $val;
    }

    function getValue(){
        return $this->value;
    }

    function ah()
    {
        return $this->ah;
    }

    function getAh()
    {
        return $this->ah();
    }
}