<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class Jqupload extends Element
{
  public $fCfg;
  public $fCfgName;
  public $_confType = 'image';
  public $uiformat = 'basic';
  public $attr;
  public $templates = array(
    'e_basic' => 'aef/fe/jqupload/e_basic.tpl',
  );
  public $jqupload = array(
    'url' => null,
    'dataType' => 'json',
    'maxNumberOfFiles' => null,
    'maxFileSize' => null,
    'acceptFileTypes' => null,
    'prependFiles' => true,
    'autoUpload' => true,
  );
  public $classes = array('fileupload-widget','single');


  function _init(){
    $this->listen('prepare', 'applyFileConfig', $this, 'applyFileConfig');
  }

  function applyFileConfig(){

    if(!$this->fCfgName){
      $aef = $this->aef->getAefByAttr('_'.$this->A->getCode().'_config');
      $this->fCfgName = $aef->getValue();
    }

    if($this->_confType == 'image'){
      $this->fCfg = \Mod\Image::getImageConfig($this->fCfgName);
    }elseif($this->_confType == 'file'){
      \Verba\_mod('file');
      $this->fCfg = file::getFileConfig($this->fCfgName);
    }

    if(!$this->jqupload['maxFileSize']){
      $this->jqupload['maxFileSize'] = $this->fCfg->getMaxUploadSize();
    }

    if(!$this->config['maxNumberOfFiles']){
      $this->config['maxNumberOfFiles'] = $this->fCfg->getMaxNumberOfFiles();
    }

    if(!$this->jqupload['acceptFileTypes']){
      $this->jqupload['acceptFileTypes'] = '/(\.|\/)('.implode('|',$this->fCfg->getExtensions()).')$/i';
    }
    if(!$this->jqupload['url']){
      $this->jqupload['url'] = $this->fCfg->getUploadUrl();
    }

  }

  function makeE(){

    $this->fire('makeE');
    $attr = !empty($this->attr) ? $this->attr : $this->acode;
    $cfg = array(
      'ot_id' => $this->oh->getID(),
      'attr' => $this->acode,
      'files' => array(),
      'fcfg' => $this->fCfgName,
      'jqu' => $this->jqupload,
    );

    $cfg['jqu']['paramName'] = $this->getName();

    if(!empty($this->value)){
      $this->value = array($this->value);
      foreach($this->value as $filename){
        $cfg['files'][] = array(
          'name' => $filename,
          'size' => @filesize($this->fCfg->getFilepath($filename)),
          'url' => $this->fCfg->getFileUrl($filename),
        );
      }
    }

    $this->tpl->assign(array(
      'JQU_ONPAGE_CONFIG' => json_encode($cfg),
      'JQU_E_ID' => $this->getId(),
      'JQU_E_NAME' => $this->getName(),
      'JQU_E_CLASS_ATTR' => $this->makeClassesTagAttr(),
      'JQU_E_FORM_ID' => $this->aef->getId(),
      'JQU_E_HIDDEN_ID' => $this->getId().'_hidden',
    ));

    $this->tpl->define(array(
      'jqupload' => $this->templates['e_'.$this->uiformat],
    ));

    $this->setE($this->tpl->parse(false, 'jqupload'));
    $this->fire('makeEFinalize');

  }
}