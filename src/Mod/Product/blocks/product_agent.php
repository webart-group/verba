<?php
class product_agent extends \Verba\Block\Html{
  /**
   * @var \Model\Item
   */
  public $prodItem;

  public $catalog;

  protected $obligatoryAttrs = array(
    'ot_id', //'id' будет добавлен автоматически в обязательные
    'price',
    'basePrice',
    'currencyId',
  );

  public $extendedAttrs = array();

  public $scripts = array(
    array('ProductAgent', 'shop')
  );

  public $templates = array(
    'content' => '/product/agent/wrap.tpl',
    'propitem' => '/product/agent/propitem.tpl',
  );

  public $tplvars = array(
    'PROD_META_PROPS' => '',
  );
  function setProdItem($val){
    if(!$val instanceof \Model\Item){
      return false;
    }
    $this->prodItem = $val;
  }

  function build(){
    $this->content = 'No req data by prod';

    if(!$this->prodItem){
      return $this->content;
    }

    $allAttrs = array_merge($this->obligatoryAttrs, $this->extendedAttrs);

    foreach($allAttrs as $attrCode){

      $A = $this->prodItem->getOh()->A($attrCode);
      if($A){
        $datatype = $A->getDataType();
      }else{
        $datatype = '';
      }

      $val = $this->prodItem->getValue($attrCode);

      $this->tpl->assign(array(
        'PROP_NAME' => $attrCode,
        'PROP_VALUE' => htmlspecialchars($val),
        'PROP_DATATYPE' => $datatype,
      ));

      $this->tpl->parse('PROD_META_PROPS', 'propitem', true);
    }
    // добавление id
    $this->tpl->assign(array(
      'PROP_NAME' => 'id',
      'PROP_VALUE' => $this->prodItem->getId(),
    ));
    $this->tpl->parse('PROD_META_PROPS', 'propitem', true);

    // добавление id Каталога если есть
    if(is_object($this->catalog)){
      $this->tpl->assign(array(
        'PROP_NAME' => 'catalogId',
        'PROP_VALUE' => $this->catalog->getId(),
      ));
      $this->tpl->parse('PROD_META_PROPS', 'propitem', true);
    }

    $this->tpl->assign(array(
      'PROD_ID' => $this->prodItem->iid,
      'PROD_OT_ID' => $this->prodItem->oh->getID(),
    ));
    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }
}
?>