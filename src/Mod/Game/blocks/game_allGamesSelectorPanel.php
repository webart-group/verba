<?php
class game_allGamesSelectorPanel extends \Verba\Block\Json{

  public $templates = array(
    'content' => '/game/hooter-selector/panel.tpl',
  );

  function build()
  {
    /**
     * @var $mGame Game
     */
    $mGame = \Verba\_mod('game');
    $allGames = $mGame->getGames();

    $r = array(
      'html' =>$this->tpl->parse(false, 'content'),
      'items' => array(
        'top' => array(),
        'alpha' => array(),
      )
    );

    /**
     * @var $mImage Image
     * @var $Game GameItem
     * @var $Service GameService
     */
    $mImage = \Verba\_mod('image');
    $iCfg = false;
    $_game = \Verba\_oh('game');
    $iCfg = \Verba\Mod\Image::getImageConfig($_game->p('icon_config'));
    foreach($allGames as $gid => $Game){

      $key = $Game->pop ? 'top' : 'alpha';
      $iconName = $Game->icon;
      if($iconName){
        $picUrl = $iCfg->getFileUrl(basename($iconName));
      }else{
        $picUrl = '';
      }

      $r['items'][$key]['i'.$Game->id] = array(
        'id' => $Game->id,
        'title' => $Game->title,
        'code' => $Game->code,
        'url' => $Game->getUrlByAction(),
        'picture' => $picUrl,
        'services' => array()
      );

      foreach($Game->getServices() as $sid => $GameService){
        $r['items'][$key]['i'.$Game->id]['services']['i'.$sid] = array(
          'id' => $sid,
          'title' => $GameService->title,
          'url' => $Game->getUrlByAction(false, $sid),
        );
      }

    }

    $this->content = $r;

    return $this->content;
  }

}
?>