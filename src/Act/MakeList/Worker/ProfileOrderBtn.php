<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class ProfileOrderBtn extends Worker{

  public $jsScriptFile = 'profileButtons';

  public $sign = '';
  public $btn_sign;
  public $code = '';
  public $urlBase;
  public $d = array();

  function init(){

    if(!is_string($this->btn_sign)){
      $this->btn_sign = $this->sign.'-btn-'.$this->code;
    }

    $this->d = \Verba\Lang::get('profile orders '.$this->sign.' workers '.$this->code);



    if($this->sign){
      if($this->sign == 'purchase' || $this->sign == 'sell'){
        /**
         * @var $mProfile Profile
         */
        $mProfile = \Verba\_mod('Profile');
        $this->urlBase = $mProfile->{'get'.ucfirst($this->sign).'ActionUrl'}();
      }
    }




  }
}
