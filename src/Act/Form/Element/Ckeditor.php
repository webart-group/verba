<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class Ckeditor extends Element
{
  public $templates = array(
    'body' => 'aef/fe/ckeditor/ckeditor.tpl',
  );
  public $config = array(
    'width' => 700,
    'resize_minWidth' => 700,
  );

  function _init(){
    $this->addClasses('ckeditor');
  }

  function setConfig($val){
    if(!is_array($val)){
      return false;
    }
    foreach($val as $configKey => $value ){
      $this->config[$configKey] = $value;
    }
  }
  function getConfig(){
    return $this->config;
  }

  function makeE(){
    $this->fire('makeE');

    $str = '';
    if(count($this->config)){
      foreach($this->config as $k => $v){
        $str .= $k . ' : \''.$v.'\', ';
      }
      $str = substr($str, 0, -2);
    }

    $this->tpl->assign(array(
      'CK_ONPAGE_CONFIG' => $str,
      'CK_AE_ID' => $this->getId(),
      'CK_AE_NAME' => $this->getName(),
      'CK_AE_CLASS_ATTR' => $this->makeClassesTagAttr(),
      'CK_AE_VALUE' => htmlspecialchars($this->getValue(), ENT_QUOTES, 'utf-8'),
    ));
    $this->aef()->addScripts(
      array('ckeditor', SYS_EXTERNALS_URL.'/ckeditor'),
      array('ckfinder', SYS_EXTERNALS_URL.'/ckfinder'));
    $this->tpl->define('ckeditor', $this->templates['body']);
    $this->setE($this->tpl->parse(false, 'ckeditor'));
    $this->fire('makeEFinalize');
  }
}