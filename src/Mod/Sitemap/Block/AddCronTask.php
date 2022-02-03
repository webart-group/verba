<?php

namespace Mod\Sitemap\Block;

class AddCronTask extends \Verba\Block\Html{

  function build(){

    \Verba\_mod('cron')->addTask('sitemap', 'generateAndReplace');

    return $this->content;
  }

}