<?php

namespace Verba\Act\AddEdit\Handler\Around;

class MailChimpEmail extends Email
{
    function run()
    {
        $val = parent::run();
        if(!$val){
            return $val;
        }

        $attr_code = $this->A->getCode();

        $qm = new \Verba\QueryMaker($this->oh->getID(), $this->oh->getBaseKey(), array($attr_code));
        $qm->addWhere("`".$attr_code."` = '".$this->DB()->escape_string($val)."'");
        if($this->action == 'edit'){
            $qm->addWhere("`".$this->oh->getPAC()."` != '".$this->ah->getIID()."'");
        }
        $qm->addLimit(1);
        $qm->makeQuery();
        $sqlr = $this->DB()->query($qm->getQuery());
        if($sqlr->getNumRows() == 1){
            $this->log()->error("Email '".$val."' already exists");
            return false;
        }
        /**
         * @var $mSubs \Subscribe
         */
        $mSubs = \Verba\_mod('subscribe');
        if(!$mSubs->addMCSubscriber($val)){
            $this->log()->error('Unable to import email to MailChimp Service');
            $this->ah->setGettedData(['mchImported' => 0]);
        }else{
            $this->ah->setGettedData(['mchImported' => 1]);
        }
        return $val;
    }
}
