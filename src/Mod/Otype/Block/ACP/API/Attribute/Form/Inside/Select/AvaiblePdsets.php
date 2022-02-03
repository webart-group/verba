<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:16
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class AvaiblePdsets extends \Verba\Block\Json {

    function build(){
        $this->content = array();
        $_pdset = \Verba\_oh('pd_set');
        $qm = new \Verba\QueryMaker($_pdset, false, array('title'));
        $qm->addWhere(1, 'active');
        $qm->addOrder(array('title' => 'a'));
        $sqlr = $qm->run();
        if(!$sqlr || !$sqlr->getNumRows()){
            return $this->content;
        }

        while($row = $sqlr->fetchRow()){
            $this->content['i'.$row['id']] = array(
                'id' => $row['id'],
                'title' => $row['title'],
            );
        }

        return $this->content;
    }

}
