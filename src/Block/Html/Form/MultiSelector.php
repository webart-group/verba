<?php

namespace Block\Html\Form;

class MultiSelector extends Element {

    public $templates = [
        'e' => 'aef/base/multi_parent_selector_e.tpl',
        'content' => 'aef/base/multi_parent_selector.tpl'
    ];

    public $wrapSelector;
    public $saveToSelector;
    public $saveUnits;
    public $currentValue = 'root';
    public $units = [];
    public $scripts = array('multi-parent-selector', 'form/e');

    protected $randSeed;


    protected function setUnits($val){

        if(!is_array($val) || !count($val)){
            return false;
        }

        $this->units = new MultiSelector\Unit($val, $this);

        return $this->units;
    }

    function prepare()
    {
        $this->randSeed = rand(0, 100000);

        if(!is_string($this->wrapSelector)){
            $this->wrapSelector = '#muse-'.$this->randSeed.'.multi-parent-selector-area';
        }
    }

    function build(){

        $this->jsCfg = array(
            'saveToSelector' => $this->saveToSelector,
            'saveUnits' => $this->saveUnits,
            'units' => $this->units,
        );

        $this->tpl->assign([
            'WRAP_SELECTOR' => $this->wrapSelector,
            'MUSE_RAND' => $this->randSeed,
            'MP_SELECTOR_CFG' => \json_encode($this->jsCfg),
        ]);
        $this->tpl->parse('WRAP', 'e');
        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }

    function getCurrentValue(){

        return $this->currentValue;

    }

    function getAllIidsForPreload(){

        return array($this->currentValue);

    }
}
