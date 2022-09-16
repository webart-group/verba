<?php
class promotion_products extends \Verba\Block\Html{

  function init(){

    $this->addCss(array(
      array('promotion')
    ));

  }

  function build(){
    $this->item = $this->_parent->item;
    $this->oh = $this->_parent->oh;
    if(!$this->item){
      throw new Exception('Item not found');
    }

    $cacheKey ='p'.$this->oh->getID().'-'.$this->request->iid;

    $_product = \Verba\_oh('product');
    $_image = \Verba\_oh('image');
    $cfg = $this->request->getParam('dcfg');
    if(!$cfg || !is_array($cfg)){
      $cfg = array();
    }
    $cfg['cfg'] = $this->request->getParam('cfg');
    if(!$cfg['cfg']){
      $cfg['cfg'] = 'public products';
    }
    $cfg['listId'] = $cacheKey;
    $cfg['block'] = $this;
    $cfg['pot'] = $this->oh->getID();
    $cfg['piid'] = $this->request->iid;

    $this->list = $_product->initList($cfg);

    $qm = $this->list->QM();

    list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());
    list($promoA, $promoT, $promoDb) = $qm->createAlias($this->oh->vltT());
    list($lpA, $lpT, $lpD) = $qm->createAlias($_product->vltT($this->oh));

    $qm->addCJoin(array(array('a' => $lpA)),
                          array(
                            array('p' => array('a'=> $lpA, 'f' => 'p_ot_id'),
                                  's' => $this->oh->getID(),
                                  ),
                            array('p' => array('a'=> $lpA, 'f' => 'p_iid'),
                                  's' => '('.implode(', ', array($this->request->iid)).')',
                                  'asis' => true,
                                  'o' => 'IN'
                                  ),
                            array('p' => array('a'=> $lpA, 'f' => 'ch_iid'),
                                  's' => array('a' => $palias, 'f' => $_product->getPAC()),
                                  ),
                          ), false, null, 'RIGHT'
                  );

    $qm->addSelect('GROUP_CONCAT(CONCAT_WS(\'^\', CAST(`'.$promoA.'`.`id` AS CHAR), CAST(`'.$promoA.'`.`title_'.SYS_LOCALE.'` AS CHAR), CAST(`'.$promoA.'`.`annotation_'.SYS_LOCALE.'` AS CHAR)) SEPARATOR \'~\')', false, 'promos', true);
    $qm->addCJoin(array(array('a' => $promoA)),
                        array(
                              array('p' => array('a'=> $promoA, 'f' => 'id'),
                                    's' => array('a'=> $lpA, 'f' => 'p_iid'),
                                    ),
                              )
                            , true);


    //Products Variants
    $pta = $palias.'_1';
    list($palias_1, $ptable_1, $db_1) = $qm->createAlias($_product->vltT(), false, $pta);
    $qm->addCJoin(array(array('a' => $palias_1)),
                          array(
                                array('p' => array('a'=> $palias_1, 'f' => 'parentId'),
                                      's' => array('a'=> $palias, 'f' => $_product->getPAC()),
                                      ),
                                )
                            , true);
    $qm->addSelect('GROUP_CONCAT(CONCAT_WS(\':\', CAST(`'.$pta.'`.`id` AS CHAR), CAST(`'.$pta.'`.`price` AS CHAR), CAST(`'.$pta.'`.`size` AS CHAR)) SEPARATOR \'#\')', false, 'variant', true);

    $qm->addGroupBy(array('id'));
    $qm->addWhere($palias.'.`active` > 0');
    $qm->addWhere('('.$palias.'.`parentId` IS NULL)');


    $this->optionsBlock = new product_listOptions($this);
    $this->optionsBlock->prepare();
    $this->optionsBlock->build();


    $this->content = $this->list->generateList();
    $q = $qm->getQuery();

    return $this->content;
  }
}
?>