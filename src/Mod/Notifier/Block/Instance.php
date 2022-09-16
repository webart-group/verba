<?php

namespace Verba\Mod\Notifier\Block;

class Instance extends \Verba\Block\Html{

    public $jsCfg = array(
        'user' => false,
        'channels' => false,
    );

    public $content = '';

    function init(){
        $this->addItems(new \centrifugo_onPageInstance($this));
    }

    function build()
    {
        $U = \Verba\User();
        if(!$U->getAuthorized()){
            return '';
        }

        $this->addScripts(array(
            array('notifier', 'common'),
        ));

        $this->jsCfg['user'] = $U->getId();

        $this->addJsBefore("
window.NotifierInstance = new Notifier('.notifier-wrap', ".\json_encode($this->jsCfg, JSON_FORCE_OBJECT).");
window.NotifierInstance.render();
window.NotifierInstance.refresh();
");
        return $this->content;
    }

    function addNotifierItem($notifier){
        if(!is_array($notifier) || !array_key_exists('code', $notifier) || !is_string($notifier['code']) || empty($notifier['code'])){
            return false;
        }

        $this->jsCfg['items'][$notifier['code']] = $notifier;

        return true;
    }
}
