<?php

namespace Verba\Act\Delete;

use ObjectType\Attribute\Handler as AttributeHandler;

class Handler extends AttributeHandler
{
    protected $_confCreatePropOnFly = true;
    /**
     * @var \Verba\Act\Delete $dh
     */
    public $ah;

    protected $set_data;

    protected $params = [];
    /**
     * @var array
     */
    protected $row;

    function setSet_data($val)
    {
        if (array_key_exists('params', $val)) {
            $this->params = $val['params'];
            unset($val['params']);
        }
        $this->set_data = $val;
    }

    function setRow($val)
    {
        if (!is_array($val)) {
            return;
        }

        $this->row = $val;
    }

    function getRow()
    {
        return $this->row;
    }
}
