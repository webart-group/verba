<?php
trait profile_orderResponse {


  public $profileSuccessMsgLKey;
  /**
   * @param $ae \Act\AddEdit
   * @return array
   */
  function wrapResponse($ae){
    $updData = $ae->getResponseByFormat('data-keys', array('status'));
    $updData['status_prev'] = $ae->getExistsValue('status');

    if(!is_string($this->profileSuccessMsgLKey) || !strlen($this->profileSuccessMsgLKey)){
      $this->profileSuccessMsgLKey = 'commonSuccess';
    }

    return array(
      'msg' => \Verba\Lang::get('profile orders msg '.$this->profileSuccessMsgLKey),
      'item' => $updData,
    );
  }

}