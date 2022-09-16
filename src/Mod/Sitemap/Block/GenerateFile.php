<?php

namespace Verba\Mod\Sitemap\Block;

class GenerateFile extends \Verba\Mod\Sitemap\Block\Generator{

  protected $context;
  protected $tempFilename;

  public $tempDir;
  public $root_catalog_id;
  public $role = 'sitemap-file';

  public $lastmod;
  public $changefreq = 'weekly';

  function init(){
    if(!is_string($this->tempDir) || strpos($this->tempDir, SYS_VAR_DIR) === false){
      $this->tempDir = SYS_VAR_DIR;
    }
  }

  function getTempFilename(){
    if($this->tempFilename === null){
      $this->tempFilename = md5(time().rand(0, 10000)).'.xml';
    }
    return $this->tempFilename;
  }

  function getTempFilepath(){
    $tfn = $this->getTempFilename();
    return $this->tempDir.'/'.$tfn;
  }

  function createContext(){
    return new \Verba\Mod\Sitemap\ContextFile($this->getTempFilepath());
  }

  function getContext(){
    if($this->context === null){
      $this->context = false;
      $this->context = $this->createContext();
    }
    return $this->context;
  }

  function getLastmod(){
    return $this->lastmod;
  }

  function getChangefreq(){
    return $this->changefreq;
  }

  function build(){
    $ctx = $this->getContext();
    if(!$ctx){
      return false;
    }

    $ctx->changefreq = $this->changefreq;
    $ctx->lastmod = date('Y-m-d');

    $ctx->write('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
    $ctx->write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">');

    // Каталог
    $b_cat = new GenerateCatalog($this, array('cat_id'=> $this->root_catalog_id));
    $b_cat->prepare();
    $b_cat->build();
    unset($b_cat);

    // Меню инфоцентр
    $b_cat = new GenerateMenu($this, array('riid'=> 316));
    $b_cat->prepare();
    $b_cat->build();
    unset($b_cat);

    // Меню FAQ
    $b_cat = new GenerateMenuFaq($this, array('riid'=> 320));
    $b_cat->prepare();
    $b_cat->build();
    unset($b_cat);

    $ctx->write('</urlset>'.PHP_EOL);
    $ctx->close();
    return $this->content;
  }
}



?>
