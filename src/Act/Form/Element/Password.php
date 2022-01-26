<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Password as HtmlPassword;

class Password extends HtmlPassword
{
  public $templates = array(
    'body' => 'aef/fe/password/password.tpl',
    'exists' => 'aef/fe/password/exists.tpl',
    'confirmation' => 'aef/fe/password/confirmation.tpl',
  );
  public $simple = false;
  public $existsAllowed = true;

  function setExistsAllowed($val){
    $this->existsAllowed = (bool)$val;
  }
  function getExistsAllowed(){
    return $this->existsAllowed;
  }

  function setSimple($val){
    $this->simple = (bool)$val;
  }
  function getSimple(){
    return $this->simple;
  }

  function makeE(){
    $this->fire('makeE');

    if($this->getSimple()){
      $r = parent::makeE();
    }else{
      $r = $this->makeENormal();
    }

    $this->setE($r);
    $this->fire('makeEFinalize');
  }

  function makeENormal(){
    $Ecfg = parent::exportAsCfg();

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    //Password Input
    $pwdE = new \Verba\Html\Password($Ecfg);
    $pwdE->setName($pwdE->getName().'[0]');
    $pwdE->setId($pwdE->getId().'_0');
    $pwdE->attr(array(
      'placeholder' => \Verba\Lang::get('profile placeholders pwd_new'),
    ));

    //Password Confirmation Input
    $pwdECnfr = new \Verba\Html\Password($Ecfg);
    $pwdECnfr->setName($pwdECnfr->getName().'[1]');
    $pwdECnfr->setId($pwdECnfr->getId().'_1');
    $pwdECnfr->attr(array(
      'placeholder' => \Verba\Lang::get('profile placeholders pwd_confirm'),
    ));


    if($this->aef->getAction() == 'edit'){
      $pwdECnfr->setValue('');
      $pwdE->setValue('');
    }

    $this->tpl->assign(array(
      'PASSWORD_FE_CONFIRMATION' => $pwdECnfr->build(),
      'PASSWORD_FE'  => $pwdE->build(),
      'PASSWORD_TITLE' => $this->A->display()
    ));

    $this->tpl->parse('PASSWORD_CONFIRMATION_BLOCK', 'confirmation');
    //Password Input Exists
    if($this->aef->getAction() == 'edit' && $this->getExistsAllowed()){
      $pwdEExists = new \Verba\Html\Password($Ecfg);
      $pwdEExists->setName($pwdEExists->getName().'[2]');
      $pwdEExists->setId($pwdEExists->getId().'_2');
      $pwdEExists->setValue('');
      $pwdEExists->attr(array(
        'placeholder' => \Verba\Lang::get('profile placeholders pwd_current'),
      ));


      $this->tpl->assign('PASSWORD_FE_EXISTS',$pwdEExists->build());
      $this->tpl->parse('PASSWORD_EXISTS_BLOCK','exists');
    }else{
      $this->tpl->assign('PASSWORD_EXISTS_BLOCK','');
    }

    $this->tpl->assign(array(
      'FORM_ID' => $this->aef()->getFormId(),
      'PASS_ID' => $pwdE->getId(),
      'PASSCONFIRM_ID' => $pwdECnfr->getId(),
    ));

    return $this->tpl->parse(false, 'body');
  }
}
