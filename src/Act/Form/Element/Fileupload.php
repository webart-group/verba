<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class Fileupload extends Element{
  public $fileuploadE = true;
  public $preview = true;

  public $templates = array(
    'body' => '/aef/fe/fileupload/fileupload.tpl',
    'file_block' => '/aef/fe/fileupload/fileuploadBlock.tpl',
    'restrictions_block' => '/aef/fe/fileupload/restrictions.tpl',
    'restrictions_row' => '/aef/fe/fileupload/restrictions_row.tpl',
  );

  function _init(){
    $this->listen('prepare', 'addFileCfgFromAttr', $this, 'handleImageConfig');
  }

  function addFileCfgFromAttr(){
    $cfgName = $this->aef->getAefByAttr('_'.$this->A->getCode().'_config')->getValue();
    $mod_file = \Verba\_mod('file');
    $this->fCfg = file::getFileConfig($cfgName);
    $this->fCfgName = $cfgName;
  }

  function setFileupload($cfg){
    if(!$cfg){
      $this->fileuploadE = (bool)$cfg;
    }
    $this->fileuploadE = new AEF_File($cfg, false, $this->aef, $this->A->getID());
  }

  function getFileuploadEId(){
    return $this->getName().'[upl]';
  }

  function getFieldsDataFromCfg(){
    $fields_array = array();
    if(count($this->aef->gC('fields'))){
      foreach($this->aef->gC('fields') as $field => $field_data){
        if(strpos($field, '_config') > 0){
          continue;
        }
        $aef = $this->aef->getAefByAttr($field);
        $fields_array[$field]['name'] = $aef->getName();
        $fields_array[$field]['title'] = \Verba\_oh('file')->A($field)->display();
      }
    }
    return $fields_array;
  }

  function parseFileUploadBlock(){
    $this->aef->addScripts(array(
      array('swfupload', SYS_EXTERNALS_URL.'/swfupload/js'),
      array('swfupload.queue', SYS_EXTERNALS_URL.'/swfupload/js'),
      array('fileprogress', SYS_EXTERNALS_URL.'/swfupload/js'),
      array('handlers', SYS_EXTERNALS_URL.'/swfupload/js'),
    ));
    $this->aef->addCSS(array(
      array('swfupload', SYS_EXTERNALS_URL.'/swfupload/css'),
    ));

    $fuload_url = \Verba\var2url($this->fCfg->getUploadUrl(), 'cfg='.$this->fCfgName);
    if(is_array($this->aef()->getParents()) && count($this->box()->aef()->getParents())){
      $fuload_url = $fuload_url.'&'.http_build_query($this->aef()->getParents(), 'parent_');
    }
    $fuload_url = $fuload_url.'&action='.$this->box()->aef()->getAction().'now&item_id='.$this->box()->aef()->getIID();

    $this->tpl->assign(array(
      'FUPLOAD_URL' => $fuload_url,
      'MAX_UPLOAD_FILESIZE' =>\FileSystem::formateFileSize($this->fCfg->getMaxUploadSize()),
      'ALLOWED_EXT_FORM' => count($this->fCfg->getExtensions()) ? '*.'.implode(';*.',$this->fCfg->getExtensions()) : '*.*',
      'OT_ID' => $this->box()->oh->getID(),
      'FCFG_NAME' => $this->fCfgName,
      'FILE_COUNT' => $this->box()->aef()->getAction() == 'edit' ? 1 : 30,
      'ADDITIONAL_FIELDS' => count($this->getFieldsDataFromCfg()) && $this->box()->aef()->getAction() == 'new' ? json_encode($this->getFieldsDataFromCfg()) : 'false',
    ));
    return $this->tpl->parse(false, 'file_block');
  }

  function setRestrictions($cfg){
    $this->restrictions = $cfg === false ? false : true;
  }

  function parseRestrictionsBlock(){
    if($this->restrictions !== false){
      $rsts = array();
      if(is_int($this->fCfg->getMaxUploadSize())){
        $rsts[] = \Verba\Lang::get('fe fileupload maxsize', array('maxFilesize' =>\FileSystem::formateFileSize($this->fCfg->getMaxUploadSize())));
      }
      if(count($this->fCfg->getExtensions())){
        $rsts[] = \Verba\Lang::get('fe fileupload allowed_ftypes', array('allowed_extensions' => implode(', ', $this->fCfg->getExtensions())));
      }
    }
    if(!count($rsts)){
      return '';
    }

    foreach($rsts as $v){
      $this->tpl->assign('RESTRICTION_ITEM', $v);
      $this->tpl->parse('RESTRICTION_ROWS', 'restrictions_row', true);
    }
    $this->tpl->assign('RESTRICTION_BOX_ID', $this->getId().'RstBlock');
    return $this->tpl->parse(false, 'restrictions_block');
  }

  function makeE(){
    $this->fire('makeE');

    $this->tpl();

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    //FileUpload Element
    $this->tpl->assign('FILEUPLOAD_FILE_BLOCK', $this->parseFileUploadBlock());

    // restrictions notifications
    $this->tpl->assign('FILEUPLOAD_RESTRICTIONS_BLOCK',$this->parseRestrictionsBlock());

    //передача имени имаж-конфига
    if(!is_object($this->fCfg)){
      throw new Exception(__METHOD__.' needs fileConfigName. Current is ['.(string)($this->getFileCfgName()).']');
    }

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}