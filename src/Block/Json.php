<?php
namespace Verba\Block;

class Json extends \Verba\Block\Html
{

    public $contentType = 'json';

    protected $operationStatus;

    function setOperationStatus($val)
    {
        $this->operationStatus = (bool)$val;
    }

    function getOperationStatus()
    {
        return $this->operationStatus;
    }

    function failed($msg = '')
    {
        $this->setOperationStatus(false);
        $this->content = (string)$msg;
    }
}