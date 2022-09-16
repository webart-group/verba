<?php
class profile_contentTraders extends profile_pageContent{

  public $Store;
  protected $storeId;

  function prepare(){

    parent::prepare();

    $this->Store = $this->U->Stores()->getStore();

    if(!$this->Store){
      $this->content = \Verba\Lang::get('store profile not_found');
      return $this->content;
    }

    $this->storeId = (int)$this->Store->id;

  }

}
?>
