<?php
namespace Mod;

class Meta extends \Verba\Mod{
    use \Verba\ModInstance;
  protected $lastConnector = ' - ';
  public $metaKeys = array('title', 'keywords', 'description');

  /**
  *Получает данные по метатегам
  *
  *@param $parents массив парентов вида
  *array(
  *  $pot_1 => array(
  *   $iid_1 => array(
  *     'id' => 1,
  *     'title' => 'Some title',
  *     'description' => 'Some description'
  *   ),
  *   $iid_2
  *  ),
  *  $pot_2 => array($iid_3, $iid_4)
  *)
  *
  *@return array(
  *  $p_ot => array(
  *    $p_iid => array(
  *      'type_1' => array(
  *
  *      ),
  *      'type_2' => array(
  *
  *      )
  *    )
  *  )
  *)
  */
  function loadObjectMeta($items){
    $_meta = \Verba\_oh('meta');
    if(!is_array($items) || !count($items)){
      return false;
    }
    $qm = new \Verba\QueryMaker($_meta, false, true, true);
    list($mtalias, $mtable, $mdb) = $qm->createAlias($_meta->vltT(), $_meta->vltDB());
    list($moltalias, $moltable) = $qm->createAlias('meta_links');
    $qm->addSelectPastFrom('p_ot_id', $moltalias);
    $qm->addSelectPastFrom('p_iid', $moltalias);
    $qm->addCJoin(array(array('a' => $moltalias)),
                  array(
                        array('p' => array('a'=> $moltalias, 'f' => 'ch_ot_id'),
                              's' => $_meta->getID(),
                              ),
                        array('p' => array('a'=> $moltalias, 'f' => 'ch_iid'),
                              's' => array('a'=> $mtalias, 'f' => $_meta->getPAC()),
                              ),
                        )
                    );
    $uCond = array();
    foreach($items as $k => $item){
      if(!isset($item['ot_id']) || !is_object($oh = \Verba\_oh($item['ot_id']))
      || !isset($item[$oh->getPAC()])){
        unset($items[$k]);
        continue;
      }
      $items[$item['ot_id'].'_'.$item[$oh->getPAC()]] = &$items[$k];
      unset($items[$k]);

      if(!array_key_exists($item['ot_id'], $uCond)){
        $uCond[$item['ot_id']] = $qm->addConditionByLinkedOT($item['ot_id']);
        $uCond[$item['ot_id']]->set_global_glue('||');
      }

      $cc = $uCond[$item['ot_id']];
      $cc->addLinkedIIDs($item[$oh->getPAC()]);
    }
    $qm->makeQuery();
    //$q = $qm->getQuery();
    if(!count($uCond)
    || !is_object($sqlr = $qm->run()) || $sqlr->getNumRows() < 1){
      return false;
    }


    while($row = $sqlr->fetchRow()){
      $k = $row['p_ot_id'].'_'.$row['p_iid'];
      $items[$k][$row['type']] = $row;
      $items[$k][$row['type']][$row['type']] = $row['meta'];
      unset($items[$k][$row['type']]['meta']);
    }

    return $items;
  }
}
?>