<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class Multi extends Element
{
  public $fields;
  public $hiddens;

  public $templates = array(
    'body' => 'aef/fe/multi/multi.tpl',
    'elements' => 'aef/fe/multi/elements.tpl',
  );

  function setFields($cfg){
    //echo 'setFields ';
    if(is_object($this->AEFExtender->extensions['reproduction']) && $this->fields == null){
      $this->makeJSConfig($cfg);
    }
    if(\Verba\reductionToArray($cfg)) $this->fields = $cfg;
  }

  function getFields(){
    return $this->fields;
  }

  function makeJSConfig($cfg){
    $this->js_cfg = array();
    if(is_array($cfg)){
      foreach($cfg as $field){
        if(!is_array($field)) continue;
        $tmp = $field['cfg'];
        $tmp['name'] = $field['cfg']['name'];
        $tmp['id'] = $field['cfg']['name'];
        switch($field['ctype']){
          case '\Html\Textarea':
            $tmp['ctype'] = 'textarea';
            break;
          case '\Html\Text':
            $tmp['ctype'] = 'text';
            break;
          case '\Html\Select':
            $tmp['ctype'] = 'select';
            break;
          case '\Html\Hidden':
            $tmp['ctype'] = 'hidden';
            break;
        }
        $this->js_cfg[$tmp['name']] = $tmp;
      }
    }
  }

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $this->tpl->assign(array(
      'MULTI_ELEMENTS_BLOCK'  => '',
      'MULTI_ELEMENTS_CELL'  => '',
    ));

    if(!$this->fields['processed']) $this->fields = array($this->fields);
    if(is_array($this->fields)){
      foreach($this->fields as $num => $set){
        if(!is_array($set)) continue;
        foreach($set as $field){
          if(is_array($field) && array_key_exists('ctype', $field)){
            if(class_exists($field['ctype'], false)){
              $field['cfg']['name'] = $this->name.'['.$num.']'.'['.$field['cfg']['name'].']';
              $field['cfg']['id'] = $field['cfg']['name'];
              $element = new $field['ctype']($field['cfg']);
              $template = !empty($field['point']) ? $field['point'] : 'MULTI_ELEMENTS_BLOCK';
              $this->tpl->assign($template, $element->build());
            }
          }
        }
        $this->tpl->assign('SET_ID', $num);
        $this->tpl->parse('MULTI_ELEMENTS_BLOCK', 'elements', true);
      }
    }

    //echo 'makeE ';
    if(is_object($this->AEFExtender->extensions['reproduction'])){
      $this->fire('reproduction');
    }

    $this->setE($this->tpl->parse(false, 'body'));
  }
}
