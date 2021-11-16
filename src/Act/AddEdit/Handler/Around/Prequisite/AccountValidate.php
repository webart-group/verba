<?php

namespace Verba\Act\AddEdit\Handler\Around\Prequisite;

use Act\AddEdit\Handler\Around;

class AccountValidate extends Around
{
    function run()
    {
        if ($this->action != 'new') {
            return $this->value;
        }

        if (!$this->value) {
            $this->log()->error(\Lang::get('prequisite form validation default'));
            return false;
        }

        /**
         * @var $Paysys \PaysysItem
         * @var $PsMod \PaySystemBase
         */

        $Cur =  \Mod\Currency::getInstance()->getCurrency($this->ah->getTempValue('currencyId'));
        $Paysys = \Verba\_mod('payment')->getPaysys($this->ah->getTempValue('paysysId'));

        if(!$Paysys || !$Cur){
            $this->log()->error(\Lang::get('prequisite form validation default'));
            return false;
        }

        $PsMod = \Verba\_mod('payment')->getPaysysMod($Paysys->getId());

        if(!$PsMod){
            $this->log()->error(\Lang::get('prequisite form validation default'));
            return false;
        }

        $this->value = $PsMod->validateAccountValue($this->value, $Cur->getId());

        if(!$this->value){
            if($Paysys->code == 'webmoney'){
                $langcode = 'webmoney '.$PsMod->getWalletLetterByCurrencyId($Cur->getId());
            }else{
                $langcode = $Paysys->code;
            }
            $this->log()->error(\Lang::get('prequisite form validation '.$langcode));
            return false;
        }

        return $this->value;
    }
}
