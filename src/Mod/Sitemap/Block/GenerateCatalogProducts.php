<?php
namespace Verba\Mod\Sitemap\Block;

class GenerateCatalogProducts extends \Verba\Mod\Sitemap\Block\Generator {

  public $cat_id;
  public $cat_item;

  function build(){

    $ctx = $this->getContext();
    $_cat = \Verba\_oh('catalog');
    $cat_ot_id = $_cat->getID();
    $cat_pac = $_cat->getPAC();

    if(!$ctx
    || !$this->cat_id
    || !$this->cat_item
    ){
      return false;
    }

    $_prod = \Verba\_oh($this->cat_item['itemsOtId']);
    $prod_ot_id = $_prod->getID();
    $pac = $_prod->getPAC();

    $qm = new \Verba\QueryMaker($_prod, false, ['url_code']);
    $qm->addConditionByLinkedOTRight($cat_ot_id, $this->cat_id);
    $qm->addWhere(1, 'active');

    $q = $qm->getQuery();

    $start = 0;
    $step = 300;

    $buy_link_parsed = false;

    do{
      $fullq = $q."\n".' LIMIT '.$start.','.$step;
      $sqlr = $this->DB()->query($fullq);
      if($sqlr && $sqlr->getNumRows()){
        while($row = $sqlr->fetchRow()){
          $url = new \Url(\Mod\Seo::idToSeoStr($row));
          $url->setFullPath($this->cat_item['fullcode']);
          $this->tpl->assign(array(
            'LOC' =>  $url->get(true),
            'LASTMOD' => $ctx->lastmod,
            'CHANGEFREQ' => $ctx->changefreq,
          ));
          $ctx->write($this->tpl->parse(false, 'url'));
        }
        $start += $step;

        // Парсинг ссылки на покупку лота
        if(!isset($buyLink)){
          if(substr($this->cat_item['fullcode'], 0,4) == '/buy'){
            $buyLink = '/sell'.substr($this->cat_item['fullcode'], 4);
            $buyLink = new \Url($buyLink);
            $this->tpl->assign(array(
              'LOC' =>  $buyLink->get(true),
              'LASTMOD' => $ctx->lastmod,
              'CHANGEFREQ' => $ctx->changefreq,
            ));
            $ctx->write($this->tpl->parse(false, 'url'));
          }else{
            $buyLink = false;
          }
        }
      }

    }while($sqlr && $sqlr->getNumRows());

    return $this->content;
  }
}



?>