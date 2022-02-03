<?php
class game_loadServicesByGame extends \Verba\Block\Json{

  function build(){

    $this->content = false;

    if(!$this->request->iid){
      return false;
    }

    $mGame = \Verba\_mod('game');

    $GameItem = $mGame->getGame($this->request->iid);
    if(!$GameItem){
      $this->setOperationStatus(false);
      return false;
    }

    $this->content = array();
    foreach($GameItem->getServices() as $srvId => $service){
      $this->content[$srvId] = array(
        'id' => $srvId,
        'title' => $service->title,
      );
    }

    return $this->content;
  }

}
?>