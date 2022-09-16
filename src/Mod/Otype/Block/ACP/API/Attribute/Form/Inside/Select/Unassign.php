<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:22
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class Unassign extends AssignActionPrototype {

    function build(){
        $this->content = array();

        $lr = $this->_attr->unlink(
            $this->A->getID(),
            array(
                $this->_pdset->getID() => array(
                    $this->pdSetData[$this->_pdset->getPAC()]
                )
            ), $this->_oh->getID(), 1);

        if(!$lr[0]) {
            throw new \Exception('Unlinking error');
        }

        $this->content = $this->_oh->getID();
        return $this->content;
    }

}