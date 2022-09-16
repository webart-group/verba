<?php
class profile_contentAndMenu extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/profile/content-and-menu/wrap.tpl',
    'default' => '/profile/content-and-menu/title-and-content.tpl'
  );
  public $tplvars = array(
    'CONTENT_TITLE' => '',
  );

  public $items = array(
    'CONTENT' => '',
    'NAV' => '',
    'SUBMENU' => '',
  );

  public $userId = null;

  public $title;

  public function init(){

    $blockMenu = new menu_Line(
      null,
      array(
        'role' => 'profile-inner-menu',
        'frash' => true
      )
    );
//    $blockSubmenu = new menu_Line(
//      null,
//      array(
//        'role' => 'profile-inner-submenu',
//        'frash' => false,
//        'templates' => array(
//          'content' => '/menu/page-inner/submenu/wrap.tpl',
//          'item' => '/menu/page-inner/submenu/item.tpl',
//        )
//      )
//    );

    // $blockMenu->listen('afterPrepare', 'setSubmenuParentId', $this, 'contentAndMenuSubmenuRootDetector');

    $this->addItems(array(
      'NAV' => $blockMenu,
      //'SUBMENU' => $blockSubmenu,
    ));
  }

  public function setSubmenuParentId(){
    $blockMenu = $this->getBlockByRole('profile-inner-menu');
    $blockSubmenu = $this->getBlockByRole('profile-inner-submenu');

    if($blockMenu->lastItemIsCurrent){
      $_menu = \Verba\_oh('menu');
      $blockSubmenu->rootId = $blockMenu->lastItem[$_menu->getPAC()];
    }

  }

  public function prepare(){
    $this->content = null;
    $U = \Verba\User();

    if(!$U->getAuthorized()){
      $this->content = '';
    }
    $this->userId = (int)$U->getID();

    $block = $this->getBlockByRole('profile-inner-menu');

    return;
  }

  function build(){
    $innerMenu = $this->getItem('NAV');
    $contentBlock = $this->getItem('CONTENT');

    if($innerMenu instanceof Block
    && $innerMenu->lastItemIsCurrent){
      $this->title = $innerMenu->lastItem['title'];
    }elseif(!empty($contentBlock->titleLangKey)){
      $this->title = \Verba\Lang::get($contentBlock->titleLangKey);
    }

    if(property_exists($contentBlock, 'coloredPanelCfg')
    && is_array($contentBlock->coloredPanelCfg)){
      $this->buildPanelColored();
    }else{
      $this->buildDefault();
    }

    $this->content = $this->tpl->parse(false, 'content');
  }

  function buildDefault(){
    if(!empty($this->title)){
      $this->tpl->assign(array(
        'CONTENT_TITLE' => $this->title,
      ));
    }
    $contentBlock = $this->getItem('CONTENT');
    $this->tpl->assign(array(
      'CONTENT' => $contentBlock->content,
    ));

    $this->tpl->parse('BLOCK_CONTENT', 'default');
  }

  function buildPanelColored(){
    $contentBlock = $this->getItem('CONTENT');

    $colorPanel = new page_coloredPanel($this, $contentBlock->coloredPanelCfg);
    $colorPanel->content = $contentBlock->content;
    $colorPanel->title = $this->title;
    $colorPanel->prepare();
    $colorPanel->build();

    $this->tpl->assign(array(
      'BLOCK_CONTENT' => $colorPanel->content,
    ));

  }


}
?>
