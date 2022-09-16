<?php
class order_emailOrderCreated extends order_email{

  protected $tpl_base = '/shop/order/email/creation';

  public $parseItems = true;
  public $parseSummary = true;

  public $infoFields = array('name','surname','phone','city','address','comment');


}
?>