<?php
class store_specify extends \Verba\Block\Json{

  function build(){

    try{
      $U = \Verba\User();
      if(!$U->getAuthorized()){
        throw new Exception('No stores for guest');
      }
      $store = $U->Stores()->getStore();
      if(is_object($store)){
        $this->content = $store->id;
      }else{
        $storeId = \Verba\_mod('Store')->create($U);
        $this->content = $storeId;
      }

      if(!is_numeric($this->content)){
        throw new Exception('Store specify error');
      }
    }catch(Exception $e){
      $this->setOperationStatus(false);
      $msg = $e->getMessage();
      if(!empty($msg)){
        $this->content = $e->getMessage();
      }
    }

    return $this->content;
  }

}
?>