<?php
class promotion_indexProducts extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/promotion/index/wrap.tpl',
  );

  function init(){
    $this->addCss(array(
      array('products promotion')
    ));
  }

  function build(){

    $cacheKey ='pcl';

    $this->oh = \Verba\_oh('product');
    $_promo = \Verba\_oh('promotion');

    $cfg = $this->request->getParam('dcfg');
    if(!$cfg || !is_array($cfg)){
      $cfg = array();
    }
    $cfg['cfg'] = $this->request->getParam('cfg');
    if(!$cfg['cfg']){
      $cfg['cfg'] = 'public products index-promotions index-groups';
    }
    $cfg['listId'] = $cacheKey;
    $cfg['block'] = $this;
    $this->list = $_product->initListFree($cfg);
    $qm = $this->list->QM();

    list($palias, $ptable, $db) = $qm->createAlias($this->oh->vltT());

    //Products Variants
    $pta = $palias.'_1';
    list($palias_1, $ptable_1, $db_1) = $qm->createAlias($this->oh->vltT(), false, $pta);
    $qm->addCJoin(array(array('a' => $palias_1)),
                          array(
                                array('p' => array('a'=> $palias_1, 'f' => 'parentId'),
                                      's' => array('a'=> $palias, 'f' => $this->oh->getPAC()),
                                      ),
                                )
                            , true);
    $qm->addSelect('GROUP_CONCAT(CONCAT_WS(\':\', CAST(`'.$pta.'`.`id` AS CHAR), CAST(`'.$pta.'`.`price` AS CHAR), CAST(`'.$pta.'`.`size` AS CHAR)) SEPARATOR \'#\')', false, 'variant', true);

    $qm->addGroupBy(array('id'));
    $qm->addWhere($palias.'.`active` > 0');
    $qm->addWhere('('.$palias.'.`parentId` IS NULL)');

    list($promoA, $promoT, $promoDb) = $qm->createAlias($_promo->vltT());
    list($lpA, $lpT, $lpD) = $qm->createAlias($_promo->vltT($this->oh));

    $qm->addCJoin(array(array('a' => $lpA)),
                          array(
                            array('p' => array('a'=> $lpA, 'f' => 'p_ot_id'),
                                  's' => $_promo->getID(),
                                  ),
                            array('p' => array('a'=> $lpA, 'f' => 'ch_iid'),
                                  's' => array('a' => $palias, 'f' => $this->oh->getPAC()),
                                  ),
                          ), false, null, 'RIGHT'
                  );

    $qm->addSelect('GROUP_CONCAT(CONCAT_WS(\'^\', CAST(`'.$promoA.'`.`id` AS CHAR), CAST(`'.$promoA.'`.`title_'.SYS_LOCALE.'` AS CHAR), CAST(`'.$promoA.'`.`annotation_'.SYS_LOCALE.'` AS CHAR)) SEPARATOR \'~\')', false, 'promos', true);
    $qm->addCJoin(array(array('a' => $promoA)),
                        array(
                              array('p' => array('a'=> $promoA, 'f' => 'id'),
                                    's' => array('a'=> $lpA, 'f' => 'p_iid'),
                                    ),
                              array('p' => array('a'=> $promoA, 'f' => 'active'),
                                    's' => '1'
                              )
                        ), true, null, 'RIGHT');
    $qm->addWhere($lpA.'.`p_iid` IS NOT NULL');
    $qm->addLimit(0,6);
    $qm->addOrder('RAND()');

    //gen list
    $this->tpl->assign(array(
      'PROMOS_LIST' => $this->list->generateList()
    ));
    $q = $qm->getQuery();

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>