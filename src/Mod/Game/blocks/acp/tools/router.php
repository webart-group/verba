<?php
namespace Mod\Game\blocks\acp\tools;

class router extends \Block{

  function route()
  {
    switch ($this->rq->node){
      case 'bids':
        $bids_rq = $this->rq->shift();
        $className = __NAMESPACE__.'\\bids\\'.$bids_rq->node;
        if(class_exists($className)){
          $h = new $className($bids_rq->shift());
        }
        break;
    }
    if(!isset($h)){
      throw new \Exception\Routing();
    }

    return $h;
  }

}