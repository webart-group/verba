<?php

class chatik_hubUI extends page_content
{

    use chatik_includes;

    public $bodyClass = 'page-chtik-hub';

    public $templates = array(
        'content' => '/chatik/hub/ui.tpl',
    );

    public $scripts = array(
        array('ChatikHub', 'chatik')
    );
    public $css = array(
        array('chatik-hub')
    );

    public $userId;
    public $backendUrl;
    public $forOt;
    public $forId;

    public $notifierCfg;

    public $jsCfg = array();

    function init()
    {
        $this->addItems(new centrifugo_onPageInstance($this));
    }

    function generateNotifierCfg()
    {
        /**
         * @var $mChat \Verba\Mod\Chatik
         */
        $mChat = \Verba\_mod('Chatik');
        $this->notifierCfg = $mChat->genNotifierCfgFor($this->notifierCfg);
        $this->notifierCfg['priority'] = 90;
    }

    function prepare()
    {

        parent::prepare();

        $this->userId =\Verba\User()->getId();

        return true;
    }

    function build()
    {
        if (!$this->forOt || !$this->forId) {
            throw new Exception('Bad params');
        }

        $this->includeEssentials();

        $this->generateNotifierCfg();

        /**
         * @var $mCent Centrifugo
         * @var $mChat Chatik
         */
        $mChat = \Verba\_mod('Chatik');

        $this->jsCfg['user'] = $this->userId;
        $this->jsCfg['items'] = $mChat->loadContactsFor($this->forOt, $this->forId);
        $this->jsCfg['backendUrl'] = is_string($this->backendUrl) && !empty($this->backendUrl)
            ? $this->backendUrl
            : $mChat->getBackendUrl();
        $this->jsCfg['for'] = $this->forOt == \Verba\_oh('store')->getID()
            ? 'store'
            : 'pers';

        if (is_array($this->notifierCfg)) {
            $this->jsCfg['notifierCfg'] = $this->notifierCfg;
        }

        $this->tpl->assign(array(
            'JSCFG' => json_encode($this->jsCfg, JSON_FORCE_OBJECT)
        ));

        $this->tpl->define(array(
            'instanceUI' => $this->getChatikInstanceTpl()
        ));

        $this->tpl->parse('CHAT_INSTANCE_UI', 'instanceUI');

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
