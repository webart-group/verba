<?php

class game_pageSell extends game_pageContent {

  public $templates = array(
    'content' => 'game/sell/wrap.tpl',
  );

  public $bodyClass = 'page-sell';

  public $css = array(
    array('sell forms', 'game'),
  );

  public $scripts = array(
    array('loot sell', 'game'),
  );

  function prepare(){

    parent::prepare();

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this));

  }

  function build(){

    $this->content = '';

    $jsCfg = array();

    if(is_object($this->gsr)){

      if(is_object($this->gsr->game)){
        $jsCfg['game'] = array(
          'id' => $this->gsr->game->id,
          'code' => $this->gsr->game->code,
        );
      }

      if(is_object($this->gsr->service)){
        $jsCfg['service'] = array(
          'id' => $this->gsr->service->id,
          'code' => $this->gsr->service->code,
        );
      }

      $jsCfg['_hideGameServiceSelector'] = is_object($this->gsr->game) && is_object($this->gsr->service);
    }

    $User = \Verba\User();
    if($User->getAuthorized()){
      $store = $User->Stores()->getStore();
      $storeId = is_object($store) ? $store->id : false;
    }else{
      $storeId = null;
    }
    $jsCfg['storeId'] = $storeId;

    //allgames data
    /**
     * @var $mGame Game
     */
    $mGame = \Verba\_mod('game');
    $allGames = array();
    foreach($mGame->getGames() as $gid => $GameItem){
      $allGames[$gid] = $GameItem->title;
    }

    $this->tpl->assign(array(
      'GAMESELLFORM_CFG' => json_encode($jsCfg),
      'ALL_GAMES' => json_encode($allGames),
      'GS_SELECTOR_TITLE' => \Verba\Lang::get('game sell gs_selector_title'),
      'NEW_BID_TITLE' => \Verba\Lang::get('game sell new_bid_title'),
    ));

    $this->content = $this->tpl->parse(false, 'content');
    \Verba\Hive::setBackURL();
    return $this->content;
  }

  function getTitle()
  {
    return \Verba\Lang::get('game sell page_title');
  }

}
?>