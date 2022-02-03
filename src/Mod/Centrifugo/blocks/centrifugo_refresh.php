<?php
class centrifugo_refresh extends \Verba\Block\Html{

  function route()
  {
    $r = new \Verba\Response\Raw($this);
    $r->addItems($this);
    return $r;
  }

  function build(){

    $this->content = false;
    $U = User();
    if (!$U->getAuthorized()) {
      $this->addHeader('Unauthorized', 403);
      return false;
    }
    $rq = json_decode(file_get_contents("php://input"), true);

    if(!$rq || !is_array($rq) || !$rq['client'] || !$rq['channels']){
      return false;
    }

    $rq['channels'] = is_array($rq['channels']) ? $rq['channels'] : [$rq['channels']];

    /**
     * @var $mCentrifugo Centrifugo
     * @var $mChat Chatik
     */
    $mCentrifugo = \Verba\_mod('Centrifugo');
    \Verba\_mod('Chatik');
    \Verba\_mod('notifier');
//
//    $response = [];
//    foreach($rq['channels'] as $channel){
//      $Channel = \Mod\WS\Channel::initObject($channel, false);
//      if(!$Channel || !$Channel->valid() || !$Channel->userHasAccess($U)){
//        $response[$channel] = ['status' => 403];
//        continue;
//      }
//
//      $response[$channel] = [
//        'sign' => $mCentrifugo->Client()->generateChannelSign($rq['client'], $Channel->name),
//        'info' => '',
//      ];
//    }

    $response = 1;

    $this->content = json_encode($response, JSON_FORCE_OBJECT);
    return $this->content;
  }

}
?>