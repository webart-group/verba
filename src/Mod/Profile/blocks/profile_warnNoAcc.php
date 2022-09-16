<?php

class profile_warnNoAcc extends \Verba\Block\Html{

  function build(){
    $this->content = 'No data to check Acc';
    $U = $this->getParent()->getU();
    if(!$U){
      return $this->content;
    }

    $Acc = $U->Accounts()->getAccount();
    if(!$Acc){
      $b = new textblock_alert(array('iid' => 'offers_account_require'), array('type'=> 'warning'));
      $b->prepare();
      $b->build();

      $this->content = $b->content;
    }else{
      $this->content = '';
    }
    return $this->content;
  }
}
?>