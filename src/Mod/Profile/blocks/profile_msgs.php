<?php
class profile_msgs extends \Verba\Block{

  function route(){
    switch($this->rq->node){
      case '':
      default:
        $h = new profile_msgsUI($this);
    }

    return $h->route();
  }

}
?>