<?php

namespace Verba\Act\Form\Element;

class MultiImageupload extends Picupload
{
  public $templates = array(
    'body' => 'aef/fe/imageupload/multiupload/imageupload.tpl',
    'file_block' => 'aef/fe/imageupload/multiupload/fileuploadBlock.tpl',
    'restrictions_block' => 'aef/fe/imageupload/multiupload/restrictions.tpl',
    'restrictions_row' => 'aef/fe/imageupload/multiupload/restrictions_row.tpl',
    'image_represent' => 'aef/fe/imageupload/multiupload/image_represent.tpl',

  );
  public $maxFiles;
  protected $maxFilesDefault = -1;

  function parseFileUploadBlock(){

    if(!is_object($fupl = $this->makeFileupload())){
      return '';
    }

    $attr_code = $this->acode;

    $formName = $this->aef->getID();
    $eId = $formName.'_'.$attr_code.'_multi';
    $fupl->setId($eId);
    $fupl->setName($this->getName());

    $this->aef->addScripts(array(
      array('jquery.MultiFile', SYS_JS_URL.'/jquery/plugins/multi-upload/js'),
    ));
    $this->aef->addCSS(array(
      array('multi-upload', SYS_JS_URL.'/jquery/plugins/multi-upload/css'),
    ));
    if($this->maxFiles === null){
      $maxFiles = $this->aef->getAction() == 'edit' ? 1 : $this->maxFilesDefault;
    }else{
      $maxFiles = intval($this->maxFiles);
    }

    $this->tpl->assign(array(
      'MF_SELECTOR' => '#'.$eId,
      'MF_MAX' => $maxFiles,
      'MULTI_IMAGE_UPLOAD_FILE_FE' => $fupl->build(),
      'IMG_ATTR_CFG_VALUE' => $this->imgCfgName,
      'IMG_ATTR_CODE' => $attr_code,
      'IMG_ATTR_CFG' => '_'.$attr_code.'_config',
      'ALLOWED_TYPES' => implode('|',$this->rstr_items['types']),
      'MF_OT_ID' => $this->aef->oh->getID(),
      'FORM_MULTIMODE' => true,
    ));
    return $this->tpl->parse(false, 'file_block');
  }

  function parseRestrictionsBlock(){
    $rsts = array();
    if($this->restrictions !== false){
      if(is_array($this->rstr_items['types']))$rsts[] = \Verba\Lang::get('fe picupload restrictions types', array('types' => implode(', ', $this->rstr_items['types'])));
      if(is_int($this->rstr_items['maxFilesize'])) $rsts[] = \Verba\Lang::get('fe picupload restrictions maxFilesize', array('maxFilesize' =>  \Verba\FileSystem::formateFileSize($this->rstr_items['maxFilesize'])));
    }
    if(is_array($this->rstr_items['types']) && in_array('zip', $this->rstr_items['types'])){
      $rsts[] = \Verba\Lang::get('fe picupload restrictions add_zip');
    }
    if(!count($rsts)){
      return '';
    }

    $this->tpl->clear_vars('RESTRICTION_ROWS');
    foreach($rsts as $v){
      $this->tpl->assign('RESTRICTION_ITEM', $v);
      $this->tpl->parse('RESTRICTION_ROWS', 'restrictions_row', true);
    }
    $this->tpl->assign('RESTRICTION_BOX_ID', $this->getId().'RstBlock');
    return $this->tpl->parse(false, 'restrictions_block');
  }

  function parseImageRepresent(){
    if(!$this->value){
      return '';
    }
    $this->tpl->assign(array(
      'MF_CURRENT_IMAGE_SRC' => $this->imgCfg->getFullUrl($this->value),
      'MF_CURRENT_IMAGE_W' => $this->aef->getExistsValue('width'),
      'MF_CURRENT_IMAGE_H' => $this->aef->getExistsValue('height'),
    ));
    return $this->tpl->parse(false, 'image_represent');
  }

  function makeE(){
    $this->fire('makeE');

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    //FileUpload Element
    $this->tpl->assign('PICUPLOAD_FILE_BLOCK', $this->parseFileUploadBlock());

    // restrictions notifications
    $this->tpl->assign('PICUPLOAD_RESTRICTIONS_BLOCK',$this->parseRestrictionsBlock());
    //image represent
    $this->tpl->assign('AVALUE_'.strtoupper($this->A->getCode()).'_IMAGE', $this->parseImageRepresent());

    $this->setE($this->tpl->parse(false, 'body'));
    $this->fire('makeEFinalize');
  }
}
