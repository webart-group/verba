<?php
class product_indexNewest extends \Verba\Block\Html{

  public $oh;
  public $templates = array(
    'content' => '/product/index/wrap.tpl',
  );

  function init(){
    $this->addCss(array(
      array('products promotion')
    ));
  }

  function build(){
    $cacheKey ='ipn';

    $this->oh = \Verba\_oh('product');
    $cfg_names = $this->request->getParam('cfg');
    if(!$cfg_names){
      $cfg_names = 'public products index-groups';
    }

    $this->list = $this->oh->initList(array(
      'cfg' => $cfg_names,
      'block' => $this,
      'listId' => $cacheKey,
    ));

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

    $_promo = \Verba\_oh('promotion');
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
                          ), false, null, 'LEFT'
                  );

    $qm->addSelect('GROUP_CONCAT(CONCAT_WS(\'^\', CAST(`'.$promoA.'`.`id` AS CHAR), CAST(`'.$promoA.'`.`title_'.SYS_LOCALE.'` AS CHAR), CAST(`'.$promoA.'`.`annotation_'.SYS_LOCALE.'` AS CHAR)) SEPARATOR \'~\')', false, 'promos', true);
    $qm->addCJoin(array(array('a' => $promoA)),
                        array(
                              array('p' => array('a'=> $promoA, 'f' => 'id'),
                                    's' => array('a'=> $lpA, 'f' => 'p_iid'),
                                    ),
                              )
                            , true);
    $qm->addOrder(array('created' => 'd'));
    //$qm->addOrder('RAND()');

    //gen list
    $this->tpl->assign(array(
      'LIST' => $this->list->generateList()
    ));
    $q = $qm->getQuery();

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>