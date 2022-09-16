<?php
\Verba\_mod('paysys_robokassa');
class Paysys_Robowm extends Paysys_Robokassa{

  function __construct($cfg){
    parent::__construct($cfg);
    $this->applyConfig('paysys_robokassa');
  }
}

?>