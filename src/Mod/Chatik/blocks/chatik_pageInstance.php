<?php

class chatik_pageInstance extends \Verba\Block\Html
{

    use chatik_includes;

    public $templates = array(
        'content' => 'chatik/instance.tpl',
    );

    public $channel;
    public $backendUrl;
    public $messages;
    public $userId;
    public $for = 'pers';
    public $lastMessageTime;
    public $unreadMessages = 0;

    public $notifierCfg;

    public $jsCfg = array();

    public $tplvars = array(
        'INSTANCE_ID' => 0,
        'CHATIK_UI' => '',
        'JS_CFG' => '[]',
    );

    function init()
    {
        $this->addItems(new centrifugo_onPageInstance($this));
    }

    function prepare()
    {
        $U = User();
        $this->userId = $U->getId();

        if (!$this->userId) {
            return null;
        }

        /**
         * @var $mCent Centrifugo
         * @var $mChat Chatik
         */
        $mChat = \Verba\_mod('Chatik');

        $this->jsCfg = array(
            'channel' => $this->channel,
            'user' => $this->userId,
            'backendUrl' => is_string($this->backendUrl) && !empty($this->backendUrl)
                ? $this->backendUrl
                : $mChat->getBackendUrl(),
            'messages' => is_array($this->messages) ? $this->messages : null,
            'for' => $this->for,
        );

        if (is_string($this->notifierCfg)) {
            $this->notifierCfg = $mChat->genNotifierCfgFor($this->notifierCfg);
        }

        if (is_array($this->notifierCfg)) {
            $this->jsCfg['notifierCfg'] = $this->notifierCfg;
        }

    }

    function build()
    {

        if (!$this->userId) {
            goto returnit;
        }

        if (!is_string($this->channel) || !$this->channel) {
            return \Verba\Lang::get('chatik error unknown_channel');
        }

        $this->includeEssentials();

        $this->tpl->define(array(
            'ui' => $this->getChatikInstanceTpl()
        ));

        $this->jsCfg['instanceId'] = 'chatik-' . rand(1, 99999999);

        $this->tpl->assign(array(
            'INSTANCE_ID' => $this->jsCfg['instanceId'],
            'CHATIK_UI' => $this->tpl->parse(false, 'ui'),
            'JS_CFG' => json_encode($this->jsCfg),
        ));

        returnit:
        return parent::build();
    }

}

