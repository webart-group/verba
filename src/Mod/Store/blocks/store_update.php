<?php
class store_update extends \Verba\Block\Html{

  function route(){

    $response = new \Verba\Response\Raw($this->rq);
    $response->addItems($this);

    return $response;
  }

  function build(){

    $this->content = false;
    //try{

      $cfg = $this->rq->asArray();
      $oh = \Verba\_oh('store');

      $this->ae = $oh->initAddEdit(array(
        'action' => 'edit',
        'iid' => $this->request->iid,
      ));

      if(!$this->ae->validateAccess()){
        throw  new \Verba\Exception\Building(Lang::get('access denied'));
      }

      if(isset($cfg['data'])){
        $this->ae->setGettedObjectData($cfg['data']);
      }else{
        $this->ae->setGettedObjectData($_REQUEST['NewObject'][$oh->getID()]);
      }

      $this->ae->addedit_object();
      $this->content = '';

      $url = \Verba\Hive::getBackURL();
      $this->addHeader('Location', $url);
      return $this->content;
/*
    }catch(Exception $e){
      if($ae->haveErrors()){
        $msg = $ae->log()->getMessagesAsStrHtml('error');
        throw  new \Verba\Exception\Building($msg);
      }
    }*/
  }

}
?>