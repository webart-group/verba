<?php
namespace Verba\Mod\Game\blocks\acp\tools;

use Verba\Block;
use Verba\Exception\Routing;

class router extends Block{

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
      throw new Routing();
    }

    return $h;
  }

}