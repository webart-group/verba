<?php
class seo_verboxInit extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'tracking/verbox/init.tpl'
  );

  function build()
  {
    $this->parent('layout')->addJsAfter(
      $this->tpl->parse(false, 'content')
    );
  }

}
?>