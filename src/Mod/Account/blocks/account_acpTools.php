<?php

class account_acpTools extends \Verba\Block {

  function route()
  {
    switch ($this->rq->node){
      case 'balancer':
        $h = new account_acpToolChangeBalance($this->rq, array(
            'op' => $this->rq->getParam('op'),
            'sum' => $this->rq->getParam('sum'),
            'block' => $this->rq->getParam('block'),
            'accId' => $this->rq->iid,
        ));
        break;
    }
    if(!isset($h)){
      throw new \Exception\Routing();
    }

    return $h->route();
  }

}
