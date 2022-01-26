<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class EmailConfirmCheck extends Extension
{
  public $timout;
  public $templates = array(
    'content' => '/aef/exts/emailConfirmCheck/content.tpl',
    'request_active' => '/aef/exts/emailConfirmCheck/request_active.tpl',
    'request_delayed' => '/aef/exts/emailConfirmCheck/request_delayed.tpl',
  );

  function engage(){
    if($this->fe->ah()->getExistsValue('email_confirmed')){
      return false;
    }
    $mUser = \Verba\_mod('User');
    $this->timout = (int)$mUser->gC('email_confirmation_resend_timeout');
    if(!$this->timout){
      $this->timout = 3600;
    }
    $this->fe->listen('makeEFinalize', 'addConfirmFunctionality', $this);
    return true;
  }

  function addConfirmFunctionality(){

    $now = time();
    $last_request_time = (int)$this->fe->ah()->getExistsValue('last_confirmation_request_time');
    $this->tpl->define($this->templates);
    if($last_request_time > 0 && $now - $last_request_time < $this->timout){

      $dateNow = new DateTime(date('Y-m-d H:i', $now));
      $dateNext = new DateTime(date('Y-m-d H:i', $last_request_time + $this->timout));
      $interval = $dateNext->diff($dateNow);

      $this->tpl->assign(array(
        'AVAIBLE_ACTION' => \Verba\Lang::get('user email_confirm delayed', array(
            'hours' => $interval->h,
            'minutes' => $interval->i)
        ),
      ));
    }else{
      $this->tpl->assign(array(
        'TRIGGER_ID' => $this->fe->getId().'_ectrg',
      ));
      $this->tpl->parse('AVAIBLE_ACTION', 'request_active');
    }



    $E = $this->tpl->parse(false, 'content');

    $this->fe->E = $this->fe->E.$E;
  }

}
