<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool;

class Store  extends User
{
    public $onlyForStores = true;

    protected $storeUrlBase;

    function init(){
        parent::init();
        $this->storeUrlBase = '/store/'.$this->U->getStoreId();

    }

    function prepareNotifierAgent()
    {

        $this->notifierAgent['channel'] = \Verba\Mod\Store::getInstance()->getStoreChannelName($this->U->getStoreId());

        parent::prepareNotifierAgent();
    }
}