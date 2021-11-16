<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class ObjectLinker extends Element
{
  public $templates = array(
    'body' => '/aef/fe/object-linker/body.tpl',
    'item' => '/aef/fe/object-linker/item.tpl',
  );
  public $direction = 'down';
  public $attrs = array(
    'title', 'priority', 'active', 'picture'
  );
  /*
   array(
     ot => array (
       'rule_alias' => str, //rule alias
       'title' => str, //element title in form
       'url' => array(
          'create' => '/acp/h/product/variant/cuform',
          'update' => '/acp/h/product/variant/cuform',
          'remove' => '/acp/h/product/variant/remove',
        ),
        'attr' => array(
          'picture', ''articul', 'price', 'size', 'size_unit', 'old_price', 'quantity'
        ),
        'item' => array(
          'selector' => 'e-selector', //optional
          'className' => 'ProductVariant',
          'tpl' => '/product/acp/aef/variant-item.tpl'
       )
       'useCurrent' => bool, //if true - use form ot as aot
       'includeDescendants' => true, // extend this rule for child otypes

     )
   )
  */
  public $aot;

  public $feats = array(
    'create' => true,
    'search' => true,
    'selector' => true,
    'edit' => true,
    'controls_disabled' => false,
  );

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define(array('body' => $this->templates['body']));

    $eId = $this->aef->getFormId().'_'.$this->acode;

    $_oh = \Verba\_oh($this->aef->oh);
    $ot_id = $_oh->getID();

    $iid = $this->aef->getIID();

    $items = array();
    $aot = array();
    $options = array();
    $rqDefaultTemplate = false;
    $fr = $this->direction == 'down' ? '1' : '2';
    reset($this->aot);

    while($i = each($this->aot)){
      $k = $i['key']; $v = $i['value'];
      $aot_cfg = $selector = false;
      if(is_numeric($v) || is_string($v)){
        $caot = $v;
      }else{
        $caot = $k;
        if(is_array($v)){
          $aot_cfg = $v;
        }
      }
      if(!$aot_cfg){
        $aot_cfg = array();
      }

      if(isset($aot_cfg['useCurrent']) && $aot_cfg['useCurrent'] == true){
        $_aoh = \Verba\_oh($ot_id);
      }else{
        $_aoh = \Verba\_oh($caot);
      }

      $aot_id = $_aoh->getID();

      if(isset($aot_cfg['includeDescendants']) && $aot_cfg['includeDescendants'] == true
        && ($dsc = $_aoh->getDescendants())){
        $dot_cfg = $aot_cfg;
        unset($dot_cfg['includeDescendants']);
        foreach($dsc as $dot){
          $_doh = \Verba\_oh($dot);
          $dot_cfg['title'] = $_doh->getTitle();
          $this->aot[$dot] = $dot_cfg;
        }
      }


      if(isset($aot_cfg['item']['tpl']) && !empty($aot_cfg['item']['tpl'])){
        if(!isset($aot_cfg['item']['selector']) || empty($aot_cfg['item']['selector'])){
          $selector = $_aoh->getCode();
        }else{
          $selector = $aot_cfg['item']['selector'];
        }
        $this->tpl->clear_tpl(array('item'));
        $this->tpl->define(array('item' => $aot_cfg['item']['tpl']));
        $this->tpl->assign(array('ITEM_SELECTOR_SIGN' => $selector));
        $this->tpl->parse('ITEMS_TEMPLATE', 'item', true);
      }else{
        $rqDefaultTemplate = true;
        $selector = 'default';
      }
      $aot[$aot_id] = array(
        'ot_id' => $aot_id,
        'iid' => $this->aef()->getIID(),
        'ot_code' => $_aoh->getCode(),
        'title' => $_aoh->getTitle(),
        'rule_alias' => false,
        'url' => array(
          'create' => false,
          'update' => false,
          'remove' => false,
        ),
        'item' => array(
          'className' => false,
          'selector' => $selector,
        )
      );

      if($rqDefaultTemplate){
        $this->tpl->clear_tpl(array('item'));
        $this->tpl->define(array('item' => $this->templates['item']));
        $this->tpl->assign(array('ITEM_SELECTOR_SIGN' => 'default'));
        $this->tpl->parse('ITEMS_TEMPLATE', 'item', true);
      }

      $aot[$aot_id] = array_replace_recursive($aot[$aot_id], $aot_cfg);
      $title = $aot[$aot_id]['title'];
      $options[$_aoh->getID()] = $title;
      if(!isset($aot[$aot_id]['attr']) || empty($aot[$aot_id]['attr'])){
        $aot[$aot_id]['attr'] = $this->attrs;
      }
    }

    $selected = array();

    $select = new \Html\Select();
    $select->addClasses('ot-selector');
    $select->setValues($options);

    if($iid){
      $mImg = \Verba\_mod('image');

      $default_fields = $this->attrs;

      foreach($aot as $aot_id => $aot_cfg){
        $_aoh = \Verba\_oh($aot_id);

        $br = \Verba\Branch::get_branch( array(
          $ot_id => array(
            'iids' => $iid,
            'aot' => $aot_id
          )
        ),
          $this->direction, 1,
          false, false,
          true, false,
          ($aot_cfg['rule_alias'] ? $aot_cfg['rule_alias'] : false)
        );

        if(!isset($br['handled'][$aot_id]) || !is_array($br['handled'][$aot_id])
          || !count($br['handled'][$aot_id])){
          continue;
        }
        $aot_items = $_aoh->getData($br['handled'][$aot_id], true, $aot_cfg['attr']);
        if(!$aot_items){
          continue;
        }
        $pac = $_aoh->getPAC();
        foreach($aot_items as $k => $row){
          $pic_url = false;
          if(isset($row['picture'])){
            if(!empty($row['picture'])){
              $iCfg = $mImg->getImageConfig($_aoh->p('picture_config'));
              $pic_url = $iCfg->getFullUrl(basename($row['picture']),'acp-list');
            }
            unset($row['picture']);
          }
          $itm_id = $row[$pac];
          $gid = $aot_cfg['ot_id'].'_'.$itm_id;
          unset($row[$pac], $row['key_id']);
          $item = $row;
          $item['id'] = $itm_id;
          $item['ot_code'] = $aot_cfg['ot_code'];
          $item['picture'] = $pic_url;
          $items[] = $item;
          $selected[$gid] = $gid;
        }
      }
    }
    $cache_id = $ot_id.'-'.$this->acode;
    $this->tpl->assign(array(
      'JS_CFG' => json_encode(array(
        'cache_id' => $cache_id,
        'ot_id' => $this->aef->oh->getID(),
        'iid' => $iid,
        'fr' => $fr,
        'E' => array(
          'formwrap' => '#'.$this->aef->getFormWrapId(),
          'wrap' => '#'.$eId,
          'form' => '#'. $this->aef->getFormId(),
        ),
        'items' => $items,
        'selected' => $selected,
        'aot' => $aot,
        'feats' => $this->feats,
      )),
      'OL_ID' => $eId,
      'OL_TYPES_SELECT' => $select->build(),
    ));

    //TOSSESSION

    $_SESSION['acp']['object-linker'][$cache_id] = array(
      'aot' => $aot
    );

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}
