<?php
class promotion_listHandlerRow extends ListHandlerRow {

  static $c = 0;

  function run(){

    self::$c++;
    $tpl = $this->list->tpl();
    $url = \Mod\Seo::idToSeoStr($this->list->row, array('seq' => $this->list->getCurrentPos(), 'slID' => $this->list->getID()));

    $tpl->assign(array(
      'ITEM_DESCRIPTION' => $this->list->row['description'],
      'ITEM_TITLE' => $this->list->row['title'],
      'ITEM_URL_CODE_VALUE' => $url,
    ));

    //return $tpl->parse(false, 'wrap');
    return 'zz';

  }

}
?>