<?php
namespace Verba\Mod;

class Blog extends \Verba\Mod{

  function makeAction(&$bp){
    switch($bp['action']){
      default:
        $handler = false;
        break;
    }
    return $handler;
  }

  function getData($iids){
    if(!\Verba\reductionToArray($iids)){
      return false;
    }
    $iids_str = "'".implode(",'", $iids)."'";
    $_image = \Verba\_oh('image');
    $_blog = \Verba\_oh('blog');
    $_tags = \Verba\_oh('tags');
    $qm = new \Verba\QueryMaker($_blog, false, true);

    list($alias, $table) = $qm->createAlias($_blog->vltT());
    list($ialias, $itable) = $qm->createAlias($_image->vltT());
    list($ilalias, $iltable) = $qm->createAlias($_blog->vltT($_image->getID()));

    $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\',
    CAST(`'.$ialias.'`.`oid` AS CHAR),
    CAST(`'.$ialias.'`.`priority` AS CHAR),
    CAST(`'.$ialias.'`.`_storage_file_name_config` AS CHAR),
    `'.$ialias.'`.`storage_file_name`))', false, 'image_data', true);
     $qm->addCJoin(array(array('a' => $ilalias)),
                    array(
                      array('p' => array('a'=> $ilalias, 'f' => 'p_iid'),
                            's' => array('a'=> $alias, 'f' => $_blog->getPAC())),
                      array('p' => array('a'=> $ilalias, 'f' => 'p_ot_id'),
                            's' => $_blog->getID()),
                      array('p' => array('a'=> $ilalias, 'f' => 'ch_ot_id'),
                            's' => $_image->getID())));
    $qm->addCJoin(array(array('a' => $ialias)),
                    array(
                      array('p' => array('a'=> $ialias, 'f' => $_image->getPAC()),
                            's' => array('t'=> $iltable, 'f' => 'ch_iid'))));

    list($tlalias, $tltable) = $qm->createAlias($_blog->vltT($_tags->getID()));
    list($talias, $ttable) = $qm->createAlias($_tags->vltT());
    $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\',
      CAST(`'.$talias.'`.`'.$_tags->getPAC().'` AS CHAR),
      `'.$talias.'`.`tag`))', false, 'tags_data', true);
    $qm->addCJoin(array(array('a' => $tlalias)),
                      array(
                        array('p' => array('a'=> $tlalias, 'f' => 'p_iid'),
                              's' => array('a'=> $alias, 'f' => $_blog->getPAC())),
                        array('p' => array('a'=> $tlalias, 'f' => 'p_ot_id'),
                              's' => $_blog->getID()),
                        array('p' => array('a'=> $tlalias, 'f' => 'ch_ot_id'),
                              's' => $_tags->getID())));

    $qm->addCJoin(array(array('a' => $talias)),
                      array(
                        array('p' => array('a'=> $talias, 'f' => $_tags->getPAC()),
                              's' => array('t'=> $tltable, 'f' => 'ch_iid'))));
    $qm->addOrder(array('priority' => 'a'), false, array($table, null, $alias));
    $qm->addGroupBy($_blog->getPAC());
    $qm->addWhere("`$alias`.`".$_blog->getPAC()."` IN ($iids_str)");

    $r = array();
    $q = $qm->getQuery();
    //dbg($q);
    if(false == ($sqlr = $this->DB()->query($q)) || !$sqlr->getNumRows()){
      return $r;
    }
    $mImage = \Verba\_mod('image');
    while($row = $sqlr->fetchRow()){
      $r[$_blog->getPAC()] = $row;
      $r[$_blog->getPAC()]['image_data'] = $mImage->getImgDataFromString($row['image_data']);
    }

    return count($iids) < 2 ? $r[$_blog->getPAC()] : $r;
  }
}
