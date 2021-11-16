<?php

namespace Verba\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

class WithdrawalSum extends Extension
{
    public $templates = array(
        'ext_func' => 'aef/exts/withdrawalSum/ext.tpl'
    );

    public $avaible_sum = 0;
    public $curId = 0;
    public $accId;

    function engage()
    {
        $this->fe->listen('makeEFinalize', 'addFunctionality', $this);
        return true;
    }

    function addFunctionality()
    {

        if (!$this->accId) {
            return null;
        }

        $cfg = array(
            'avaible_sum' => $this->avaible_sum,
            'msgs' => \Verba\Lang::get('withdrawal form msgs'),
        );

        $this->ah()->addScripts(['withdrawal', 'profile']);

        $this->tpl->define($this->templates);

        $this->tpl->assign(array(
            'CUR_UNIT' => \Mod\Currency::getInstance()->getCurrency($this->curId)->symbol
        ));

        $this->ah()->addJsAfter("
    $(document).ready(function(){
      var wdrSum = new withdrawalSumExt('#" . $this->ah()->getWrapId() . "', " . json_encode($cfg) . ");
      wdrSum.render();
      wdrSum.handlePreqChange();
      wdrSum.handleTotalChange();
    });");

        $this->fe()->setE($this->fe()->getE() . $this->tpl->parse(false, 'ext_func'));
    }
}
