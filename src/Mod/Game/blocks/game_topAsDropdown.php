<?php

class game_topAsDropdown extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'game/top_game_dropdown/wrap.tpl',
    'item' => 'game/top_game_dropdown/item.tpl',
  );
  public $tplvars = array();

  public $gameAction;
  public $games;

  function build(){
    /**
     * @var $mGame Game
     * @var $CatItem GameItem
     */
    $mGame = \Verba\_mod('game');

    if($this->games === null){
      $this->games = \Verba\_mod('game')->getTopGames();
    }

    if(!is_array($this->games) || !count($this->games)){
      return $this->content;
    }

    $mImage = \Verba\_mod('image');
    $_game = \Verba\_oh('game');
    $iCfg = \Verba\Mod\Image::getImageConfig($_game->p('icon_config'));
    foreach($this->games as $catalogId => $CatItem){

      if(!$CatItem->icon){
        $aclass = ' no-logo';
        $logo = '';
      }else{
        $logo = 'background-image: url(\''.$iCfg->getFullUrl(basename($CatItem->icon)).'\');';
        $aclass = '';
      }

      $linkUrl = $CatItem->getUrlByAction($this->gameAction);

      $this->tpl->assign(array(
        'ITEM_URL' => $linkUrl,
        'ITEM_BACKGROUND_IMAGE' => $logo,
        'ITEM_ACLASS' => $aclass,
        'ITEM_TITLE' => $CatItem->title,
      ));
      $this->tpl->parse('ITEMS', 'item', true);
    }

    $this->tpl->assign(array(
      'ACTION' => $this->gameAction,
    ));

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>