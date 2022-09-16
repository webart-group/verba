<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:19
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class Assign extends AssignActionPrototype {

    function build(){
        $this->content = array();



        $lr = $this->_attr->link(
            $this->A->getID(),
            array(
                $this->_pdset->getID() => array(
                    $this->pdSetData[$this->_pdset->getPAC()]
                )
            ), $this->_oh->getID(), 1);

        if(!$lr[0]) {
            throw new \Exception('Linking error');
        }

        $this->content['item'] = Init::createAssignClientCfg(
            [
                'pd_set_id' => $this->pdSetData[$this->_pdset->getPAC()],
                'pd_set_title' => $this->pdSetData['title'],
                'default_value' => null,
                'attr_id' => $this->A->getID(),
                'ot_id' => $this->_oh->getID(),
                'ot_code' => $this->_oh->getCode(),
                'ot_title' => $this->_oh->getTitle(),
            ]
        );

        return $this->content;
    }

}