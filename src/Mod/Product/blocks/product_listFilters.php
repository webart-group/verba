<?php

class product_listFilters extends \Verba\Block\Html{

  public $list;

  function init(){
    $this->list = $this->getParent()->list;
    $this->list->listen('afterFilters', 'build', $this);
  }

  function build(){

    $catalogBlock = $this->getParent('catalog_products');
    $catalogBlock->tpl->asg('CATALOG_FILTERS', $this->list->tpl()->getVar('LIST_FILTERS'));
    $this->list->tpl()->asg('LIST_FILTERS', '');

    return $this->content;
  }

}
?>