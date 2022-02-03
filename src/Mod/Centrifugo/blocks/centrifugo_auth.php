<?php

class centrifugo_auth extends \Verba\Block\Html
{

    function route()
    {
        $r = new \Verba\Response\Raw($this);
        $r->addItems($this);
        return $r;
    }

    function build()
    {

        $this->content = false;
        $U = User();
        if (!$U->getAuthorized()) {
            $this->addHeader('Unauthorized', 401);
            return false;
        }
        $rq = json_decode(file_get_contents("php://input"), true);

        if (!$rq || !is_array($rq) || !$rq['client'] || !$rq['channels']) {
            return false;
        }

        $rq['channels'] = is_array($rq['channels']) ? $rq['channels'] : [$rq['channels']];

        /**
         * @var $mCentrifugo \Mod\Centrifugo
         * @var $mChat \Mod\Chatik
         */
        $mCentrifugo = \Verba\_mod('Centrifugo');
        \Verba\_mod('Chatik');
        \Verba\_mod('notifier');

        $channels = [];
        foreach ($rq['channels'] as $channel) {
            $Channel = \Mod\WS\Channel::initObject($channel, false);
            if (!$Channel || !$Channel->valid() || !$Channel->userHasAccess($U)) {
                continue;
            }
            $obj = new \stdClass;
            $obj->channel = $Channel->getName();
            $obj->token = $mCentrifugo->Client()->generatePrivateChannelToken($rq['client'], $Channel->name);
            $channels[] = $obj;
        }

        $response = new stdClass;
        $response->channels = $channels;

        $this->content = json_encode($response);
        return $this->content;
    }
}
