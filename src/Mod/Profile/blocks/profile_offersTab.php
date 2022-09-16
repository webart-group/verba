<?php
class profile_offersTab extends profile_contentTraders{

  public $coloredPanelCfg = false;
  public $titleLangKey = false;
  public $templates = array(
    'content' => '/profile/offers/tab/tab.tpl'
  );

  public $bodyClass = 'profile-offers';

  public $tplvars = array(
    'ALERT_ACCOUNT_REQUIRE' => '',
    'ALERT_FIRST_BID_CONGRAT' => '',
    'ALERT_EMAIL_CONFIRM_REQUIRED' => '',
  );

  function init(){

    parent::init();

    $this->addItems(array(
      'ALERT_ACCOUNT_REQUIRE' => new profile_warnNoAcc($this),
    ));
  }

  function prepare(){
    parent::prepare();

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this));

    $this->addCss(array(
      array('gamebidsui', 'profile'),
      array('storebids', 'profile'),
      array('forms', 'game'),
    ), 500);

    $this->addScripts(array(
      array('gamebidsui', 'profile'),
    ), 500);

    $this->addCss(array(
        array('list list-layout-table'),
    ), \Act\MakeList::CSS_PRIORITY);


    $this->addScripts(array(
        array('list-tools', 'engine/act/makelist'),
        array('workers', 'engine/act/makelist'),
        array('StoreOfferItem', 'engine/act/makelist/workers'),
    ), \Act\MakeList::CSS_PRIORITY);

    \Verba\Lang::sendToClient('list');
  }

  function build(){

    if(is_string($this->content)){
      return $this->content;
    }

    $Store = $this->U->Stores()->getStore();
    // Первая покупка
    if($Store->first_offer > 0
      && (/*(time() - $Store->first_offer <= 3600*24*30) || */!($Store->tips_mask & 1))){
      $b = new textblock_alert(array('iid' => 'offers_first_bid_created'), array(
        'type'=> 'success',
        'parseContent' => true,
      ));
      $b->tpl()->assign(array(
        'CURRENT_STORE_TITLE' => htmlentities($Store->title)
      ));
      $b->prepare();
      $b->build();

      $bProves = new textblock_alert(array('iid' => 'offers_first_bid_proves'), array(
        'type'=> 'warning',
      ));
      $bProves->prepare();
      $bProves->build();

      $this->tpl->assign(array(
        'ALERT_FIRST_BID_CONGRAT' => $b->content . $bProves->content,
      ));
      \Verba\_oh('store')->update($Store->id, array('tips_mask' => 1));
    }

    // Подтверждение емейла
    if(!$this->U->email_confirmed){
      $b = new textblock_alert($this->rq, array(
        'type'=> 'warning',
        'text' => \Verba\Lang::get('profile warns email_confirm_required', array(
          'profile_url' => \Verba\_mod('user')->getProfileUrl(),
        ))
      ));
      $b->prepare();
      $b->build();
      $this->tpl->assign(array(
        'ALERT_EMAIL_CONFIRM_REQUIRED' => $b->content,
      ));
    }

    // Подтверждения выполнения
    if(!$this->U->email_confirmed){
      $b = new textblock_alert($this->rq, array(
        'type'=> 'warning',
        'text' => \Verba\Lang::get('profile warns email_confirm_required', array(
          'profile_url' => \Verba\_mod('user')->getProfileUrl(),
        ))
      ));
      $b->prepare();
      $b->build();
      $this->tpl->assign(array(
        'ALERT_EMAIL_CONFIRM_REQUIRED' => $b->content,
      ));
    }

    $mGame = \Verba\_mod('game');

    //Исходные данные
    $gameId =  $this->rq->getParam('gameId', true);
    $serviceId = false;

    if($gameId){
      $Game = $mGame->getGame($gameId);
      if(!$Game || !$Game->active){
        $gameId = false;
      }else{
        $gameId = $Game->id;
        $serviceId = $this->rq->getParam('serviceId', true);
        if($serviceId){
          $Service = $Game->getService($serviceId);
          if(!$Service || !$Service->active){
            $serviceId = false;
          }else{
            $serviceId = $Service->id;
          }
        }
      }
    }
    // МультиСелектор
    $multiSelectorCfg = array(
      'saveToSelector' => false,
      'saveUnits' => 'all',
      'units' => array(
        'name' => 'gameId',
        'currentValue' => $gameId,
        'eName' => 'gameId',
        'url' => false,
        'emptyOptionAllowed' => false,
        'valuesGenerator' => array(
          'handler' => array($mGame, 'getGamesForMultiSelector'),
        ),
        'preload' => 'all',
        'children' => array(
          'name' => 'serviceId',
          'currentValue' => $serviceId,
          'eName' => 'serviceId',//'parent['._oh('currency')->getID().']',
          'url' => '/game/get-services-by-game',
          'emptyOptionAllowed' => true,
          'valuesGenerator' => array(
            'handler' => array($mGame, 'getServicesForMultiSelector'),
            'args' => is_numeric($serviceId) ? array($serviceId) : null,
          ),
          //'preload' => 'all',
        )
      )
    );

    $ms = new \Verba\Block\Html\Form\MultiSelector(false, $multiSelectorCfg);
    $ms->prepare();
    $ms->build();
    $this->mergeHtmlIncludes($ms);

    if($gameId && $serviceId){
      $curTabId = $gameId.'-'.$serviceId;
    }else{
      $curTabId = false;
    }

    $pgb_ui_js_cfg = array(
      'currentTabId' => $curTabId,
      'url'=> array('list' => '/profile/offers/list'),
      'gameSelector' => false,
    );

    $this->tpl->assign(array(
      'PGB_UI_JS_CFG' => json_encode($pgb_ui_js_cfg, JSON_FORCE_OBJECT),
      'MP_SELECTOR' => $ms->content,
      'MUSE_E_SELECTOR' => $ms->gC('wrapSelector'),
    ));

    $this->content = $this->tpl->parse(false, 'content');

    \Verba\Hive::setBackURL();

    return $this->content;
  }

}
?>
