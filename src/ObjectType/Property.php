<?php
namespace Verba\ObjectType;


class Property extends \Verba\Configurable
{

    public $id;
    public $code;
    public $type;
    public $value;
    public $title;
    public $display;
    public $inheritable;
    public $priority;

    /**
     * @var \Verba\Model
     */
    private $oh;
    /**
     * @var \ObjectType
     */
    private $OT;

    protected $_confPropsMeta = array(
        'inheritable' => array('dataType' => 'boolean')
    );
    protected $_confPropsMetaExtend = array();

    function __construct($data, $OT)
    {

        if (count($this->_confPropsMetaExtend)) {
            $this->_confPropsMeta = array_replace_recursive($this->_confPropsMeta, $this->_confPropsMetaExtend);
        }

        if (!is_array($data)) {
            return false;
        }

        $this->OT = $OT;
        $this->oh = $OT->getOh();

        $this->applyConfigDirect($data);

        return true;
    }

    static function createProperty($data, $OT){

        $className = false;

        if(!empty($data['type'])){
            $type = ucfirst(strtolower($data['type']));
            $className = '\Verba\ObjectType\Property\\'
                . ($type == 'String' || $type == 'Float' ? '_' : '')
                . $type;
        }

        if(!$className || !class_exists($className)){
            $className = '\Verba\ObjectType\Property\_String';
        }
        return new $className($data, $OT);
    }

    function getId()
    {
        return $this->id;
    }

    function getCode()
    {
        return $this->code;
    }

    function getValue()
    {
        return $this->value;
    }

    function value()
    {
        return $this->getValue();
    }

    function v()
    {
        return $this->getValue();
    }

    function getTitle()
    {
        return $this->title;
    }

    function getDisplay()
    {
        return $this->display;
    }

    function getInheritable()
    {
        return $this->inheritable;
    }
}
