<?php
class product_acpListHandlerTitle extends ListHandlerField{

  function run(){

    $this->tpl->define(array(
      'wrap' => 'product/acp/list/titleAndPicture.tpl',
    ));

    if(empty($this->list->row['picture'])){
      $this->tpl->assign(array(
        'ITEM_PICTURE' => '',
      ));
    }else{
      $mImage = \Verba\_mod('image');
      $imgCfg = $mImage->getImageConfig($this->list->oh()->p('picture_config'));
      if(!($src = $imgCfg->getFullUrl(basename($this->list->row['picture']), 'acp-list'))){
        $src = $imgCfg->getFullUrl(basename($this->list->row['picture']));
      }
      $this->tpl->assign(array(
        'ITEM_PICTURE' => '<div><img src="'.$src.'"></div>',
      ));
    }
    $this->tpl->assign(array(
      'ITEM_TITLE' => $this->list->row['title'],
    ));

    return $this->tpl->parse(false, 'wrap');
  }

}
?>