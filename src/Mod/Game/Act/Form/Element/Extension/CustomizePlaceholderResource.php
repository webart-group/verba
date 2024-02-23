<?php
namespace Verba\Mod\Game\Act\Form\Element\Extension;

use Verba\Act\Form\Element\Extension;

class CustomizePlaceholderResource extends Extension{

  /**
   * @var \FEExt_addPlaceholder
   */
  protected $addPlaceholderExt;
  public $langKey;
  public $req;

  protected $src_oh;

  function engage(){
    $this->fe->listen('prepare', 'hookPlaceholderCustomize', $this);
  }

  function hookPlaceholderCustomize(){

    $this->addPlaceholderExt = $this->fe->getExtension('addPlaceholder');
    if($this->addPlaceholderExt){
      $this->addPlaceholderExt->listen('beforePlaceholder', 'run', $this);
    }
  }

  function getSrcOh(){
    return $this->ah()->getOh();
  }

  function run(){
    $oh = $this->getSrcOh();

    $placeholderValue = $this->addPlaceholderExt->generatePlaceholder();

    if(!is_string($placeholderValue)){
      return $placeholderValue;
    }

    $unitSymbol = $oh->p('unitSymbol');

    $params = [
      'su' =>$oh->p('su'),
      'unitSymbol' => $unitSymbol,
      'scale' => $oh->p('scale'),
    ];

    if(is_string($this->req) && array_key_exists($this->req, $params)){
      if(!$params[$this->req]){
        return $placeholderValue;
      }
    }


    $ext = '';

    if(is_string($this->langKey)){
      $ext = \Verba\Lang::get('game placeholders ph_customizer '.$this->langKey, $params);
    }else{
      if(!empty($unitSymbol)){
        $ext = ','.(!empty($params['su']) ? ' '.$params['su']:'').' '.$unitSymbol;
      }
    }

    $this->addPlaceholderExt->placeholder = $placeholderValue . $ext;
  }
}
