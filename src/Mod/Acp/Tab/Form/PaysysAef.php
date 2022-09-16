<?php

namespace Verba\Mod\Acp\Tab\Form;


class PaysysAef extends AEForm{
  public $button = array(
    'title' => 'paysys acp tab title'
  );
  public $ot = 'paysys';
  public $url = '/acp/h/paysys/cuform';
  public $instanceTo = array('type' => 'node');
}
