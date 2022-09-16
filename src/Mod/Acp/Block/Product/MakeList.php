<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 19:48
 */

namespace Verba\Mod\Acp\Block\Product;


class MakeList extends \Verba\Block\Html
{

    public $select_temp = false;

    function build()
    {
        $_product = \Verba\_oh('product');
        $_catalog = \Verba\_oh('catalog');
        $cat_ot_id = $_catalog->getID();
        $cfg = $this->request->asArray();
        list($pot, $piid) = $this->request->getFirstParent();

        // try to include subcatalogs if ony one catalog is as a parent
        if (count($cfg['pot']) == 1
            && count($cfg['pot'][$cat_ot_id]) == 1) {
            $br = \Verba\Branch::get_branch(array($cat_ot_id => array('aot' => $cat_ot_id, 'iids' => array($piid))), 'down', 100, false);
            $cfg['pot'][$cat_ot_id] = $br['handled'][$cat_ot_id];
        }

        $oh = \Verba\_oh($cfg['ot_id']);

        $list = $oh->initList($cfg);
        $qm = $list->QM();

//    list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());

        //$qm->addGroupBy(array('id'));
        //$qm->addWhere('('.$palias.'.`parentId` IS NULL)', false, 'whr_ProdId_Is_Not_Null');

        //Products Variants
        //$pta = $palias.'_1';
//    list($palias_1, $ptable_1, $db_1) = $qm->createAlias($_product->vltT(), false, $pta);
//    $qm->addCJoin(array(array('a' => $palias_1)),
//                          array(
//                                array('p' => array('a'=> $palias_1, 'f' => 'parentId'),
//                                      's' => array('a'=> $palias, 'f' => $_product->getPAC()),
//                                      ),
//                                )
//                            , true);
//    $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\', CAST(`'.$pta.'`.`id` AS CHAR), CAST(`'.$pta.'`.`price` AS CHAR), CAST(`'.$pta.'`.`size` AS CHAR), CAST(`'.$pta.'`.`size_unit` AS CHAR), CAST(`'.$pta.'`.`articul` AS CHAR)) SEPARATOR \'#\')', false, 'variant', true);

        $this->content = $list->generateList();
        $q = $list->QM()->getQuery();
        return $this->content;
    }
}