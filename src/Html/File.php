<?php
namespace Verba\Html;

class File extends Input{

    public $type = 'file';
    /**
     * @var null|[] types to generate accept attribute
     */
    protected $acceptTypes = null;

    function setSize($var){
        if(($var = intval($var)) && $var > 0){
          $this->size =  $var;
        }
    }
    function getSize(){
        return $this->size;
    }
    function makeSizeTagAttr(){
        return is_int($this->size) ? "size=\"{$this->size}\"" : '';
    }

    function setAcceptTypes($val){
        if(is_string($val)){
            $val = [$val];
        }
        if(is_array($val)){
            $this->acceptTypes = $val;
        }
        return $this->acceptTypes;
    }

    function getAcceptTypes(){
        $this->acceptTypes;
    }

    function makeE(){
        $this->fire('makeE');
        $tag = $this->getTag();


        if($this->acceptTypes){
            $acceptTypes = ' accept=".'.implode(',.',$this->acceptTypes).'"';
        }else{
            $acceptTypes = '';
        }

        $this->setE("<" . $tag . $this->prepareEAttrsImploded() . $acceptTypes."/>");
        $this->fire('makeEFinalize');
    }
}
