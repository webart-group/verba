<?php
namespace Mod\Game;

class Service extends \Verba\Model\Item
{
    /**
     * @var \Mod\Game\Item
     */
    protected $_parent;

    function __construct($parent, $data)
    {
        $this->_parent = $parent;
        parent::__construct($data);
    }

    function setConfig($val)
    {
        $var = is_string($val) && !empty($val)
            ? unserialize($val)
            : array();
        $this->{$this->_confPropName}['config'] = $var;
    }

    function getUrlByAction($action)
    {
        return $this->_parent->getUrlByAction($action, $this->id);
    }
}
