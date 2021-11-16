<?php
namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class OptionsInjectToRightHeaderCell extends Worker{

  public $hdr_i = 0;
  public $total_headers_count;

  function init(){
    $this->parent->listen('headerCellBefore', 'run', $this);
  }

  function run(){
    if($this->total_headers_count === null){
      $this->total_headers_count = is_array($this->parent->headersToParse)
        ? count($this->parent->headersToParse)
        : 0;
    }
    if(!$this->total_headers_count ){
      return null;
    }
    $this->hdr_i++;
    if($this->hdr_i == $this->total_headers_count){
      if(!isset($this->parent->headerCfg['class'])){
        $this->parent->headerCfg['class'] = '';
      }
      $this->parent->headerCfg['class'] .= ' options-button-injected';
    }
  }
}
