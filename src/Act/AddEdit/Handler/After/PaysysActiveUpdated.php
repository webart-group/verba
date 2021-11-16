<?php
namespace Verba\Act\AddEdit\Handler\After;

class PaysysActiveUpdated extends CurpsActiveUpdated{

  function run(){

    if(!$this->validate()){
      return false;
    }

    $this->updatePrequisitesActivity();

    return true;
  }
}
