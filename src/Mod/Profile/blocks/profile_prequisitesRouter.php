<?php
class profile_prequisitesRouter extends \Verba\Block{

  function route(){
    switch($this->rq->node){
      case 'list':
        $b = new profile_prequisitesActions($this->rq->shift());
        break;
    }

    if(!isset($b)){
      throw new \Verba\Exception\Routing();
    }

    return $b->route();
  }

}
?>
