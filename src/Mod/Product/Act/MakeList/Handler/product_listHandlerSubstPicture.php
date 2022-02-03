<?php
class product_listHandlerSubstPicture extends ListHandlerField{
  public $type = 'small';

  function run(){
    $mImage = \Verba\_mod('image');
    try{
      $imgCfg = $mImage->getImageConfig($this->list->oh()->p('picture_config'));
      $iUrl = $imgCfg->getFullUrl(basename($this->list->row['picture']), $this->type);
    }catch(Exception $e){
      $iUrl = false;
    }

    $tpl = $this->list->tpl();
    if(is_string($iUrl) && !empty($iUrl)){
      $tpl->assign(array(
        'HAS_IMAGE_REL_ATTR' => ' rel="have-image"',
        'ITEM_PICTURE_URL' => $iUrl,
        'ITEM_PICTURE_AS_BG' => 'style="background-image: url(\''.$iUrl.'\');"',
      ));
    }else{
      $tpl->assign(array(
        'HAS_IMAGE_REL_ATTR' => '',
        'ITEM_PICTURE_URL' => '',
        'ITEM_PICTURE_AS_BG' => '',
      ));
    }
    return $iUrl;
  }
}
?>
