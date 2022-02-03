<?php

namespace Mod\Sitemap\Block;

class Info extends \Verba\Block\Html{

  public $content = array(
    'modified' => '',
    'exists' => false,
    'size' => 0,
    'path' => false,
    'url' => false,
  );

  function build(){
    try{
      $this->content['path'] = \\Verba\_mod('sitemap')->getFilePath();
      $url = new \Url(\Verba\_mod('sitemap')->getFileUrl());
      if(!file_exists($this->content['path'])
        || !($stat = stat($this->content['path']))
        || !$url){
        throw  new \Verba\Exception\Building();
      }
      $this->content['url'] = $url->get(true);
      $this->content['exists'] = true;
      $this->content['size'] = $stat['size'];
      $this->content['modified'] = date('Y-m-d H:i:s', $stat['mtime']);

    }catch( \Verba\Exception\Building $e){

    }
    return $this->content;
  }

}