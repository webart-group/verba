<?php
class product_list extends \Verba\Block\Html{

  public $list;
  public $parseFilters = true;
  public $optionsBlock;
  public $isFiltersEnable = true;


  function init(){
    $this->addCss(array(
      array('products promotion')
    ));
  }

  function build(){

    $pot = $this->request->getParam('pot');
    $piid = $this->request->getParam('piid');
    $_catalog = \Verba\_oh('catalog');
    if(!is_numeric($pot) || !is_numeric($piid)
    || !is_array($catData = $_catalog->getData($piid, 1))
    || !isset($catData['active']) || !$catData['active']
    ){
      $this->log()->warning('Requested Products list for unactive Catalog (id: '.var_export($piid, true).'). Canceled.');
      return '';
    }

    $cacheKey ='c'.$pot.'-'.$piid;
    $ccfg = is_string($catData['config']) && !empty($catData['config'])
      ? unserialize($catData['config'])
      : array();

    $ot = isset($ccfg['ot']) && $ccfg['ot'] ? $ccfg['ot'] : 'product';
    $_product = \Verba\_oh($ot);

    $_image = \Verba\_oh('image');

    $branch = \Verba\Branch::get_branch(array($_catalog->getID() => array('aot' => array($_catalog->getID()), 'iids'=> array($piid))), 'down', 3, false, false);
    $catIids = array($piid);
    if(is_array($branch['handled'][$_catalog->getID()])
    && count($branch['handled'][$_catalog->getID()])){
      $catIids += $branch['handled'][$_catalog->getID()];
    }

    if(!count($catIids)){
      return '';
    }

    $cfg = $this->request->getParam('dcfg');
    if(!$cfg || !is_array($cfg)){
      $cfg = array();
    }
    $cfg['cfg'] = $this->request->getParam('cfg');
    if(!$cfg['cfg']){
      $cfg['cfg'] = 'public products';
    }

    $cfg['listId'] = $cacheKey;
    $cfg['pot'] = $pot;
    $cfg['piid'] = $piid;
    $cfg['block'] = $this;

    $this->list = $_product->initList($cfg);

    $qm = $this->list->QM();

    list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());
    list($ctalias, $ctable, $cdb) = $qm->createAlias($_catalog->vltT());
    list($lcA, $lcT, $lcD) = $qm->createAlias($_product->vltT($_catalog));
    $qm->addGroupBy(array('id'));
    $qm->addWhere('0', 'active', false, array($ptable, $db, $palias), '>');

    //подключение таблицы связей каталог-продукты
    $qm->addCJoin(array(array('a' => $lcA)),
                          array(
                            array('p' => array('a'=> $lcA, 'f' => 'p_ot_id'),
                                  's' => $_catalog->getID(),
                                  ),
                            array('p' => array('a'=> $lcA, 'f' => 'p_iid'),
                                  's' => '('.implode(', ', $catIids).')',
                                  'asis' => true,
                                  'o' => 'IN'
                                  ),
                            array('p' => array('a'=> $lcA, 'f' => 'ch_iid'),
                                  's' => array('a' => $palias, 'f' => $_product->getPAC()),
                                  ),
                          ), false, null, 'RIGHT', 'obligatory'
                  );

    // подключение таблицы каталога для выборки данных каталога
    $qm->addSelectPastFrom('title_ru', $ctalias, 'ctitle');
    $qm->addSelectPastFrom('code', $ctalias, 'ccode');
    $qm->addSelectPastFrom($_catalog->getPAC(), $ctalias, 'catId');
    $qm->addCJoin(array(array('a' => $ctalias)),
                          array(
                            array('p' => array('a'=> $lcA, 'f' => 'p_iid'),
                                  's' => array('a'=> $ctalias, 'f' => $_catalog->getPAC()),
                                  ),
                          ), true, null
                  );

    //Promos
    $_promo = \Verba\_oh('promotion');
    list($promoA, $promoT, $promoDb) = $qm->createAlias($_promo->vltT());
    list($lpA, $lpT, $lpD) = $qm->createAlias($_promo->vltT($_product));
    $qm->addCJoin(array(array('a' => $lpA)),
                          array(
                            array('p' => array('a'=> $lpA, 'f' => 'p_ot_id'),
                                  's' => $_promo->getID(),
                                  ),
                            array('p' => array('a'=> $lpA, 'f' => 'ch_ot_id'),
                                  's' => $_product->getID(),
                                  ),
                            array('p' => array('a'=> $lpA, 'f' => 'ch_iid'),
                                  's' => array('a' => $palias, 'f' => $_product->getPAC()),
                                  ),
                          ), true, null, 'LEFT'
                  );

    $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\'^\', CAST(`'.$promoA.'`.`id` AS CHAR), CAST(`'.$promoA.'`.`title_'.SYS_LOCALE.'` AS CHAR), CAST(`'.$promoA.'`.`annotation_'.SYS_LOCALE.'` AS CHAR)) SEPARATOR \'~\')', false, 'promos', true);
    $qm->addCJoin(array(array('a' => $promoA)),
                        array(
                              array('p' => array('a'=> $promoA, 'f' => 'id'),
                                    's' => array('a'=> $lpA, 'f' => 'p_iid'),
                                    ),
                              )
                            , true);

    //Products Variants
    $qm->addWhere('', 'parentId', false, array($ptable, $db, $palias), 'IS NULL');
    $pta = $palias.'_1';
    list($palias_1, $ptable_1, $db_1) = $qm->createAlias($_product->vltT(), false, $pta);
    $qm->addCJoin(array(array('a' => $palias_1)),
                          array(
                                array('p' => array('a'=> $palias_1, 'f' => 'parentId'),
                                      's' => array('a'=> $palias, 'f' => $_product->getPAC()),
                                      ),
                                array('p' => array('a'=> $palias_1, 'f' => 'active'),
                                      's' => '1',
                                      ),
                                )
                            , true);
    $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\', CAST(`'.$pta.'`.`id` AS CHAR), CAST(`'.$pta.'`.`price` AS CHAR), CAST(`'.$pta.'`.`size` AS CHAR), CAST(`'.$pta.'`.`size_unit` AS CHAR)) SEPARATOR \'#\')', false, 'variant', true);

    if($this->parseFilters){
      $filtertBlock = new product_listFilters($this);
    }

    $this->content = $this->list->generateList();
    $q = $qm->getQuery();

    $this->optionsBlock = new product_listOptions($this);
    $this->optionsBlock->prepare();
    $this->optionsBlock->build();

    return $this->content;
  }
}
?>
