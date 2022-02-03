<?php
class callback_add extends \Verba\Block\Html{

  function build(){
    $mCallback = \Verba\_mod('callback');
    $oh = \Verba\_oh('callback');
    try{
      $ae = $oh->initAddEdit('create');
      $ae->setGettedObjectData(array(
        'phone' => $_REQUEST['phone'],
        'comment' => $_REQUEST['comment'],
      ));

      $iid = $ae->addedit_object();
      //sending email
      if($iid){
        if(!$mCallback->sendNewCallbackEmail($ae->getObjectData())){
          //throw new Exception($mCallback->log()->getMessagesAsStr());
        }
      }else{
        throw new Exception($ae->log()->getMessagesAsStr());
      }

    }catch(Exception $e){
      $this->content = $e->getMessage();
      if(isset($e->ae) && is_object($e->ae)){
        $ae_errors = "\nAE:\n".$e->ae->log()->getAllMessages('error');
      }else{
        $ae_errors = '';
      }

      $this->log()->error('Bad Callback Request. Message:'.$e->getMessage().$ae_errors."\nDump:\n".var_export($_REQUEST, true));
      throw $e;
    }

    $this->content = $iid;

    return $this->content;
  }



}
?>