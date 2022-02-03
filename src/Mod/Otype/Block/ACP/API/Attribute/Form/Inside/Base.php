<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:09
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form\Inside;


class Base extends \Verba\Block\Json
{

    public $params = [];

    protected $_attr;
    protected $attrData;

    function prepare()
    {
        $this->_attr = \Verba\_oh('ot_attribute');

        if ($this->rq->iid) {
            $this->attrData = $this->_attr->getData($this->rq->iid, 1);
        }
    }

    function build()
    {
        $this->content = '';
        return $this->content;
    }
}