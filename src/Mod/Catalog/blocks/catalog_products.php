<?php

class catalog_products extends \Verba\Block\Html{

  public $catsData;
  public $currentCat;

  public $templates = array(
    'tpl' => 'catalog/show/block.tpl',
  );
  public $tplvars = array(
    'LIST_SORT_SELECTOR' => '',
    'CATALOG_FILTERS' => '',
    'CATALOG_FILTERS_SIGN' => '',
    'CATALOG_DESCRIPTION' => '',
  );
  public $role = 'catalog_products';

  function init(){

    $_catalog = \Verba\_oh('catalog');
    $mCat = \Verba\_mod('catalog');
    $this->catsData = $this->request->getParam('catsData');
    if(!$this->catsData){
      $this->catsData = $mCat->getCatsChain($this->request->uf, 0);
    }
    if(!$this->catsData){
      throw new \Exception\Routing();
    }
    $this->currentCat = end($this->catsData);
    if(!$this->currentCat['active']){
      throw new \Exception\Routing();
    }

    $this->request->addParam(array(
      'pot' => $_catalog->getID(),
      'piid' => $this->currentCat[$_catalog->getPAC()],
      'cfg' => 'public products'
    ));
    if(is_string($this->currentCat['config']) && !empty($this->currentCat['config'])
    && is_array($ccfg = unserialize($this->currentCat['config']))
    && isset($ccfg['filters'])
    && is_array($ccfg['filters'])
    && !empty($ccfg['filters'])
    ){
      $this->request->addParam(array(
        'dcfg' => array(
          'filters' => array(
            'items' => $ccfg['filters']
          )
        )
      ));
    }

    $this->addItems(array(
      'CATALOG_PRODUCTS' => new product_list($this),
    ));

    if(!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])){
      $this->addItems(array(
        'CATALOG_DESCRIPTION' => new catalog_pbphl($this, array(
          'items' => array(new catalog_description($this))
        )),
      ));
    }
  }

  function build(){

    $this->tpl->assign(array(
      'CATALOG_EMPTY_CLASS' => $this->items['CATALOG_PRODUCTS']->list->getNumRows() ? '' : ' catalog-empty',
      'CATALOG_CODE' => $this->currentCat['code'],
      'CATALOG_TITLE' => isset($this->currentCat['exttitle']) && !empty($this->currentCat['exttitle']) ? $this->currentCat['exttitle'] : $this->currentCat['title'],
      'CATALOG_FILTERS_SIGN' => $this->tpl->getVar('CATALOG_FILTERS') ? ' no-filters' : '',
      'LIST_COLUMNS_SIGN' => $this->tpl->getVar('CATALOG_FILTERS') ? ' col-4' : ' col-3',
      'LIST_GREEN_OPTIONS' => $this->items['CATALOG_PRODUCTS']->optionsBlock->content,
    ));

    $this->content = $this->tpl->parse(false, 'tpl');
    if(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])){
      $url = new \Url($_SERVER['SCRIPT_URL']);
      $this->addHeadTag('link', array('rel' => 'canonical', 'href'=>$url->get(true)));
    }
    return $this->content;
  }

}
?>