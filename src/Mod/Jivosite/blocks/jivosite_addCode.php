<?php
class jivosite_addCode extends \Verba\Block\Html{

  public $content = '';
  public $templates = array(
    'content' => '/jivosite/code.tpl'
  );

  function init(){
    if(!SYS_IS_PRODUCTION){
      $this->mute();
      return;
    }
  }

  function build(){
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

}
?>
