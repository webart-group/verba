<?php
class chatik_confirmRead extends \Verba\Block\Json{


  function build(){
    /**
     * @var $mCent Centrifugo
     * @var $mChatik Chatik
     */

    $mCent = \Verba\_mod('centrifugo');
    $mChatik = \Verba\_mod('Chatik');

    if(!$mCent->verifyClientToken($_REQUEST['token'], $_REQUEST['user'])){
      throw  new \Verba\Exception\Building('Error issue');
    }

    $userId = (int)$_REQUEST['user'];
    $channel = $_REQUEST['channel'];
    $chtime = (int)$_REQUEST['chtime'];

    if(empty($channel) || !$userId || !$chtime){
      throw  new \Verba\Exception\Building('Bad data');
    }

    $U = User();

    if(!$userId || $U->getId() != $userId || !$U->active){
      throw  new \Verba\Exception\Building('User error');
    }

    /**
     * @var $Channel \Mod\Chatik\Channel\Store
     */
    $Channel = \Mod\WS\Channel::initObject($channel);
    if(!$Channel || !$Channel->valid()){
      throw  new \Verba\Exception\Building('Channel not found');
    }
    list($forOt, $forId) = $Channel->getForWho(isset($_REQUEST['for']) ? $_REQUEST['for'] : false);
    if(!$Channel->updateChecked($chtime, $forOt, $forId)){
      throw  new \Verba\Exception\Building('Op error');
    }

    $this->content = true;

    return $this->content;

  }


}
?>