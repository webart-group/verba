<?php
trait product_listHandlerField{

  static $size_unit_pd_values;

  function getSizeUnitPdValue($pd_id){
    if(self::$size_unit_pd_values === null){
      self::loadSizeUnitPdValues();
    }
    return is_array(self::$size_unit_pd_values) && array_key_exists($pd_id, self::$size_unit_pd_values)
      ? self::$size_unit_pd_values[$pd_id]
      : false;
  }

  static function loadSizeUnitPdValues(){
    $oh = \Verba\_oh('product');
    self::$size_unit_pd_values = $oh->A('size_unit')->getValues();
  }

}
