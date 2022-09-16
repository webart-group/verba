<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.09.19
 * Time: 19:06
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute;

class Load extends \Verba\Block\Json {

    function build(){
        $iid = $this->request->iid;
        if(!$iid && isset($_REQUEST['ot_iid'])){
            $iid = (int)$_REQUEST['ot_iid'];
        }
        if(!$iid){
            throw new \Exception('Bad incoming data');
        }
        $_ot = \Verba\_oh('otype');
        $_thisOt = \Verba\_oh($iid);
        $_attr = \Verba\_oh('ot_attribute');
        $baseId = $_thisOt->getBaseId();
        $cfg = array(
            'cfg' => 'acp/list acp/otype-attrs',
        );
        $list = $_attr->initList($cfg);

        $list->addParents($_ot->getID(), $iid);
        $qm = $list->QM();

        $listHtml = $list->generateList();
        $q = $qm->getQuery();
        $this->content = $listHtml;
        return $this->content;
    }

}