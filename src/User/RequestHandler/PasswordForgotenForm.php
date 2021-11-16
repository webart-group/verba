<?php
namespace Verba\User\RequestHandler;

class PasswordForgotenForm extends \Verba\Block\Html{

  public $menuId = null;

  public $templates = array(
    'content' => 'user/reclaimpass/form.tpl'
  );

  function route(){
    $b = new page_coloredPanel($this,
      array(
        'title' => \Verba\Lang::get('user reclaim_pass title'),
        'width' => 'half-size'
      )
    );
    $b->addItems($this);
    return $b;
  }

  function prepare(){
    $this->addCss(
      array('form'),
      array('reclaim-access')
    );
    $this->addScripts(
      array('form formValidator','form'),
      array('password-reset-ui','common')
    );

    $tf = new \Verba\Data\Email(array());
    $tf->setValue(trim($_POST['email']));
    if($tf->validate()){
      $email = $tf->getValue();
    }else{
      $email = '';
    }
    $jsCfg = array(
      'url' => array(
        'email_send' => '/user/pwd-reset-code-request',
        'code_send' => '/user/reset-pwd-form',
        'reset_now' => '/user/reset-pwd',
      )
    );


    $this->tpl->assign(array(
      'JS_CFG' => json_encode($jsCfg, JSON_FORCE_OBJECT),
      'EMAIL' => $email,
    ));
  }

}
?>