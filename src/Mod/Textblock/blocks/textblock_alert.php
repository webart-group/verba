<?php
class textblock_alert extends textblock_getBlock{

  public $templates = array(
    'content' => '/textblock/alert.tpl',
  );

  public $title = false;
  public $type = 'info';
  public $isDissmissible = true;

  protected $allowed_types = array('success', 'warning', 'info', 'danger');

  function prepare(){
    $this->type = $this->type && in_array($this->type, $this->allowed_types)
      ? $this->type
      : 'info';

    $this->tpl->assign(array(
      'TYPE' => $this->type,
      'DISMISSIBLE' => $this->isDissmissible ? ' alert-dismissible' : '',
    ));
  }
}
?>