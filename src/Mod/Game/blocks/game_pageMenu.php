<?php

class game_pageMenu extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'game/page/nav/nav.tpl',
    'services_block' => 'game/page/nav/services.tpl',
    'service_item' => '/menu/page-inner/item.tpl',
  );

  public $color_scheme = 'blue';
    /**
     * @var \Verba\Mod\Game\ServiceRequest
     */
    public $gsr;

  function build(){

    $this->content = '';

    if(
    !is_object($this->gsr)
    || !$this->gsr->isValid()
    || empty($this->gsr->gameAction)
    )
    {
      return '';
    }

    $mImage = \Verba\_mod('Image');
    $icon = $this->gsr->game->icon;
    $_game = \Verba\_oh('game');
    if(!empty($icon)
    && is_object($iconCfg = $mImage->getImageConfig($_game->p('icon_config')))){
      $icoUrl = $iconCfg->getFullUrl(basename($icon));
      $icoStyleImage = "background-image: url('".$icoUrl."');";
    }else{
      $icoStyleImage = '';
    }

    $this->tpl->assign(array(
      'GAME_TITLE' => $this->gsr->game->title,
      'GAME_STYLE_ICON' => $icoStyleImage,
      'GAME_SERVICES' => '',
      'SERVICES_ITEMS' => '',
      'SERVICE_SELECTED_SIGN' => '',
      'GAME_COLOR_SCHEME' => $this->color_scheme,
    ));

    $selectedServiceId = $this->gsr->service->getId();

    if(is_array($services = $this->gsr->game->getServices()) && !empty($services)){
      foreach($services as $sid => $Service){
        $selected = $sid == $selectedServiceId ? 'selected' : '';
        $this->tpl->assign(array(
          'URL' => $this->gsr->game->getUrlByAction($this->gsr->gameAction, $Service->id),
          'TITLE' => $Service->title,
          'SELECTED_CLASS_SIGN' => $selected,
        ));

        $this->tpl->parse('SERVICES_ITEMS', 'service_item', true);
      }
      $this->tpl->parse('GAME_SERVICES', 'services_block');
    }

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>