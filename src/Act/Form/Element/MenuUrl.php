<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Text;
use \Verba\Html\Hidden;

class MenuUrl extends Text
{
  public $prefix = null;
  public $templates = array(
    'body' => 'aef/fe/url/url.tpl',
  );

  function makeE(){
    $this->fire('makeE');

    $Ecfg = parent::exportAsCfg();
    $urlE = new Text($Ecfg);

    $aef = $this->aef();
    $prefixE = new Hidden();
    $this->detectPrefix();
    $prefixE->setValue($this->prefix);
    $prefixE->setName('NewObject['.$aef->getOtId().']['.$this->acode.'_prefix]');
    if(is_string($this->prefix) && !empty($this->prefix)){
      $val = $urlE->getValue();
      if(is_string($val) && !empty($val)
        && mb_strpos($val, $this->prefix) === 0 ){
        $val = mb_substr($val, mb_strlen($this->prefix));
        $urlE->setValue($val);
      }
    }

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $this->tpl->assign(array(
      'URL_PREFIX_SPAN_CLASS' => 'url-prefix-holder',
      'PREFIX' => $this->prefix,
      'URL_FE' => $urlE->build(),
      'HIDDEN' => $prefixE->build()
    ));

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }

  function detectPrefix(){
    if(!is_string($this->prefix) || empty($this->prefix)){
      $this->prefix = \Verba\_mod('menu')->detectMenuItemParentPrefix($this->aef, $this->A, $this->aef->getExistsValue('inherit_url'));
    }
    if(is_string($this->prefix) && !empty($this->prefix)){
      $this->prefix = rtrim($this->prefix, '/').'/';
    }
    return $this->prefix;
  }
}
