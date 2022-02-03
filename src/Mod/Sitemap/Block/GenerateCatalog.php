<?php

namespace Mod\Sitemap\Block;
class GenerateCatalog extends \Verba\Mod\Sitemap\Block\Generator {

  public $cat_id = 0;
  public $item;

  function build(){

    $ctx = $this->getContext();
    $_cat = \Verba\_oh('catalog');
    $cat_ot_id = $_cat->getID();
    $cat_pac = $_cat->getPAC();


    if(!is_array($this->item)){
      $this->item = $_cat->getData($this->cat_id, 1);
    }

    if(!$ctx
    || !$this->cat_id
    || !$this->item
    || !isset($this->item['fullcode'])
    || empty($this->item['fullcode'])
    || !$this->item['fullcode']){
      return false;
    }

    if(!!$this->item['hidden']){
      goto HANDLE_PRODUCTS;
    }

    $cat_url = new \Url($this->item['fullcode']);
    $this->tpl->assign(array(
      'LOC' => $cat_url->get(true),
      'LASTMOD' => $ctx->lastmod,
      'CHANGEFREQ' => $ctx->changefreq,
    ));

    $ctx->write("\n".$this->tpl->parse(false, 'url'));


    HANDLE_PRODUCTS:

    if($this->item['itemsOtId']){
      $prods = new GenerateCatalogProducts($this, array('cat_id' => $this->cat_id, 'cat_item' => $this->item));
      $prods->prepare();
      $prods->build();
      unset($prods);
    }

    $q = "
SELECT
`b`.`fullcode`,
`b`.`active`,
`b`.`itemsOtId`,
`b`.`ot_id`,
`b`.`key_id`,
`b`.`id`
FROM ".$_cat->vltURI()." b
RIGHT JOIN  ".$_cat->vltURI($_cat)." as `lt` ON `lt`.`ch_iid` = b.`".$cat_pac."`
WHERE `b`.`active` != 0 && `lt`.`p_ot_id` = '".$cat_ot_id."' && `lt`.`ch_ot_id` = '".$cat_ot_id."' && `lt`.`p_iid` = '".$this->cat_id."'
LIMIT ";
    $start = 0;
    $step = 300;

    do{
      $fullq = $q.$start.','.$step;
      $sqlr = $this->DB()->query($fullq);
      if($sqlr && $sqlr->getNumRows()){
        while($row = $sqlr->fetchRow()){
          $b_cat = new GenerateCatalog($this, array('cat_id' => $row[$cat_pac], 'item' => $row));
          $b_cat->prepare();
          $b_cat->build();
          unset($b_cat);
        }
        $start += $step;
      }

    }while($sqlr && $sqlr->getNumRows());

    return $this->content;
  }

}
