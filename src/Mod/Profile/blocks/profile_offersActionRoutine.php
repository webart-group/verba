<?php

trait profile_offersActionRoutine{

  function init(){
    $prod = \Verba\_oh($this->rq->ot_id);
    if($prod->getRole() == 'public_product'){
      $this->valid_otype = $prod->getCode();
    }
  }
}
?>
