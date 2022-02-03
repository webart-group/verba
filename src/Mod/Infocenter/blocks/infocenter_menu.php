<?php
class infocenter_menu extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/ic/page/menu.tpl'
  );

  function init()
  {
    $this->addItems(array(
      'MENU_TREE' => new infocenter_struct($this),
      'FAQ_TREE' => new faq_struct($this),
    ));
  }

}
?>