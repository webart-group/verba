<?php
namespace Verba\Mod\Banner\Act\MakeList\Handler\Field;

class Pic extends \Act\MakeList\Handler\Field {

  function run(){

    if(empty($this->list->row['picture'])){
      return '';
    }

    $mImage = \Verba\_mod('image');
    $imgCfg = $mImage->getImageConfig($this->list->oh()->p('picture_config'));
    if(!($src = $imgCfg->getFullUrl(basename($this->list->row['picture']), 'acp-list'))){
      return '';
    }
    return '<div style="max-width:300px;max-height:200px; overflow: hidden;"><img src="'.$src.'" class="acp-banner-preview-thumb"/></div>';
  }
}
