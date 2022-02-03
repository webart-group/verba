<?php
class store_info extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'store/info/content.tpl',
  );

  /**
   * @var $Store \Model\Store
   */
  public $Store;


  function init(){
    if(!$this->Store instanceof \Model\Store &&  is_numeric($this->Store)){
      $this->Store = new \Model\Store($this->Store);
    }

    if(!$this->Store instanceof \Model\Store || !$this->Store->getId()){
      throw new \Exception\Routing('Unknown store');
    }

    return true;
  }

  function route(){

//    if($this->Store->getID() < 20 && !\Verba\_mod('acp')->checkAccess()){
//      throw new \Exception\Routing();
//    }

    $listId = 'or_'._oh('store')->getID().'_'.$this->Store->getId();
    $reviewBaseUrl = new \Url(\Mod\Store::getInstance()->getPublicUrl($this->Store->getId(), 'info'));
    $reviewBaseUrl->shiftPath('review');
    $reviewBaseUrl = $reviewBaseUrl->get();

    if($this->rq->node === 'review'){
      $rq = $this->rq->shift();
      $bReviews = new store_reviewsAndForm($rq, array(
        'urlBase' => $reviewBaseUrl,
        'Store' => $this->Store,
        'listId' => $listId,
      ));
      return $bReviews->route();
    }

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this->rq));

    $mStore = \Mod\Store::getInstance();

    $this->addItems(array(

      'PANEL_STORE_INFO' => new store_infoAndAnnounces($this, array('Store' => $this->Store)),

      'PANEL_STORE_REVIEWS' => new page_coloredPanel($this, array(
        'items' => array(new store_reviewsAndForm($this, array(
          'urlBase' => $reviewBaseUrl,
          'Store' => $this->Store,
          'listId' => $listId,
        ))),
        'title' => \Verba\Lang::get('store reviews panelTitle'),
        'scheme' => 'green',
      )),
      'PANEL_STORE_CHAT' => new page_coloredPanel($this, array(
        'items' => array('CONTENT' => new \chatik_pageInstance($this, array(
          'channel' => $mStore->genChatChannelToUser($this->Store),
          'notifierCfg' => 'user'
        ))),
        'title' => \Verba\Lang::get('profile orders chat panelTitle'),
        'scheme' => 'grey',
      )),
    ));

    return $this;

  }

  function prepare(){

    $this->tpl->assign(array(
      'STORE_URL' => \Mod\Store::getInstance()->getPublicUrl($this->Store->getId())
    ));

  }

}
?>