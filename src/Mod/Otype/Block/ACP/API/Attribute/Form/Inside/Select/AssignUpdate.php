<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:23
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class AssignUpdate extends AssignActionPrototype {

    protected $extData = array();

    function prepare(){

        parent::prepare();

        $this->extData = $this->rq->getParam('fields');
        if(!is_array($this->extData) || !count($this->extData)){
            throw new \Verba\Exception\Routing('Fields not found');
        }

    }

    function build(){

        $this->content = 0;


        $lr = $this->_attr->updateLink(
            $this->A->getID(),
            array(
                $this->_pdset->getID() => array(
                    $this->pdSetData[$this->_pdset->getPAC()]
                )
            ), $this->_oh->getID(), 1, $this->extData);

        if(!$lr[0]) {
            throw  new \Verba\Exception\Building('Link update error');
        }

        $this->content = 1;

        return $this->content;
    }

}