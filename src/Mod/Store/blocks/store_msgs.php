<?php
class store_msgs extends \Verba\Block {

  /**
   * @var $Store \Model\Store
   */
  public $Store;

  function route(){
    $U = User();
    if(!$U->getAuthorized() || !$this->Store instanceof \Model\Store || $this->Store->owner != $U->getId()){
      throw new \Exception\Routing();
    }

    switch($this->rq->node){
      case '':
        $h = new store_msgsUI($this, array('Store' => $this->Store));
        break;
      default:
        throw new \Exception\Routing();
    }

    return $h->route();
  }

}
?>