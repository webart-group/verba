<?php

namespace Verba\Act\AddEdit;

use Verba\ObjectType\Attribute\Handler as AttributeHandler;

class Handler extends AttributeHandler
{
    /**
     * @var \Verba\Act\AddEdit
     */
    public $ah;

    protected $_confCreatePropOnFly = true;

    protected $action;

    protected $_allowedEdit = true;
    protected $_allowedNew = true;

    function init()
    {
        parent::init();
        $this->action = $this->ah->getAction();
    }

    function isAllowed($action)
    {
        $propName = '_allowed' . ucfirst($action);
        return property_exists($this, $propName) ? $this->{$propName} : null;
    }
}
