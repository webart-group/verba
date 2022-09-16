<?php
class store_msgs extends \Verba\Block {

  /**
   * @var $Store \Model\Store
   */
  public $Store;

  function route(){
    $U = \Verba\User();
    if(!$U->getAuthorized() || !$this->Store instanceof \Model\Store || $this->Store->owner != $U->getId()){
      throw new \Verba\Exception\Routing();
    }

    switch($this->rq->node){
      case '':
        $h = new store_msgsUI($this, array('Store' => $this->Store));
        break;
      default:
        throw new \Verba\Exception\Routing();
    }

    return $h->route();
  }

}
?>