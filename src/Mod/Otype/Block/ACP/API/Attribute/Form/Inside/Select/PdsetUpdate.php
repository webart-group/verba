<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:26
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class PdsetUpdate extends \Verba\Block\Json {


    function build(){

        if(!$this->rq->getParam('pdset_id')){
            throw  new \Verba\Exception\Building('Pdset not found');
        }

        $this->content = 0;
        $_pd_set = \Verba\_oh('pd_set');

        $ae = $_pd_set->initAddEdit(array('iid' => $this->rq->getParam('pdset_id')));
        $ae->setGettedData($this->rq->getParam('fields'));
        $ae->addedit_object();

        if(!$ae->isUpdated()) {
            throw  new \Verba\Exception\Building('Pd set not updated');
        }

        $this->content = 1;

        return $this->content;
    }

}