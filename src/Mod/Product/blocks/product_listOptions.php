<?php

class product_listOptions extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/product/list/options/u_body.tpl',
    'ronp' => '/product/list/options/ronp.tpl',
  );

  function build(){
    $list = $this->getParent()->list;

    $this->tpl->assign(array(
      'RONP_SELECTOR' => '',
    ));

    if($list->isRonpSelectorUpper()){
      $this->tpl->assign(array(
        'SR_RONP_SELECTOR_UPPER' => $list->tpl()->getVar('SR_RONP_SELECTOR_UPPER'),
      ));
      $this->tpl->parse('RONP_SELECTOR', 'ronp');
    }

    if($list->isCurrentRangeUpper()){
      $this->tpl->assign(array(
        'SR_CURRENT_RANGE_UPPER' => $list->tpl()->getVar('SR_CURRENT_RANGE_UPPER'),
      ));
    }else{
      $this->tpl->assign(array(
        'SR_CURRENT_RANGE_UPPER' => '',
      ));
    }
    if(is_array($list->workers) && !empty($list->workers)){
      foreach($list->workers as $alias => $z){
        if($alias != 'CustomOrder'){
          continue;
        }
        if(property_exists($z, 'select')){
          $s = $z->getSelect();
          if(is_object($s)){
            $selectHtml = $s->parse();
          }
        }
        break;
      }
    }

    $this->tpl->assign(array(
      'PRICE_SELECTOR' => isset($selectHtml) && is_string($selectHtml) ? $selectHtml : '', //$selectE->parse()
      'LIST_ID' => $list->getId(),
    ));
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

}
?>