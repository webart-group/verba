<?php
class callback_agentHooter extends callback_tools{

  public $templates = array(
    'content' => '/callback/agent/hooter/wrap.tpl'
  );

  function prepare(){
    $this->getBlockByRole('default-modal')->unmute();
  }

  function build(){
    $mCallback = \Verba\_mod('callback');
    $oh = \Verba\_oh('callback');
    try{
      $jsCfg = array(
        'url' => array(
          'create' => '/callback/add',
        ),
      );
      $this->tpl->assign(array(
        'JS_CFG' => json_encode($jsCfg),
      ));
      $this->content = $this->tpl->parse(false, 'content');

    }catch(Exception $e){
      $this->content = $e->getMessage();
    }
    return $this->content;
  }



}
?>