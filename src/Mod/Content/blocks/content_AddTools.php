<?php
class content_AddTools extends \Verba\Block\Html{

  function build(){
    $this->addScripts('contentTools', 'common');
    $this->addCss('contentTools');
    $this->addJsAfter("
$('.sw-trigger').each(function(i,e){
  assignContentAutoswitchers(e);
}
);
//convertImagesLinksToGallery();");


  }
}
?>