<?php

namespace Verba\Act\Form\Element;

class Imageupload extends Picupload
{
  public $templates = array(
    'body' => 'aef/fe/imageupload/imageupload.tpl',
    'file_block' => 'aef/fe/imageupload/fileuploadBlock.tpl',
    'preview_block' => 'aef/fe/imageupload/preview.tpl',
    'restrictions_block' => 'aef/fe/imageupload/restrictions.tpl',
    'restrictions_row' => 'aef/fe/imageupload/restrictions_row.tpl',
    'image_represent' => 'aef/fe/imageupload/image_represent.tpl',
  );

  function getFileuploadEId(){
    return $this->getName();
  }

  function parseRestrictionsBlock(){
    $rsts = array();
    if($this->restrictions !== false){
      if(is_array($this->rstr_items['types']))$rsts[] = \Verba\Lang::get('fe picupload restrictions types', array('types' => implode(', ', $this->rstr_items['types'])));
      if(is_int($this->rstr_items['maxFilesize'])
      && $this->rstr_items['maxFilesize'] > 0) {
          $rsts[] = \Verba\Lang::get('fe picupload restrictions maxFilesize', array('maxFilesize' =>  \Verba\FileSystem::formateFileSize($this->rstr_items['maxFilesize'])));
      }
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
