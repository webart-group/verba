<?php
class profile_publicView extends page_content {

  public $bodyClass = 'profile-public';

  public $templates = array(
    'content' => '/profile/pubview/content.tpl'
  );

  public $tplvars = array(
    '' => '',
  );

  /**
   * @var $U \Verba\Mod\User\Model\User
   */
  public $U;

  function init(){
    if(!$this->U instanceof \Verba\Mod\User\Model\User && is_numeric($this->U)){
      $this->U = new \Verba\Mod\User\Model\User($this->U);
    }

    if(!$this->U instanceof \Verba\Mod\User\Model\User || !$this->U->getID()){
      throw new \Verba\Exception\Routing('Unknown store');
    }

    return true;
  }

  function route(){

    $reviewBaseUrl = new \Url(\Mod\Profile::getInstance()->getPublicUrl($this->U->getID()));
    $reviewBaseUrl->shiftPath('reviews');
    $reviewBaseUrl = $reviewBaseUrl->get();

    $reviewsBlockCfg = array(
      'urlBase' => $reviewBaseUrl,
      'userId' => $this->U->getID(),
      'listId' => 'or_'._oh('user')->getID().'_'.$this->U->getID(),
      'where' => array(
        'active' => 1,
      )
    );

    if($this->rq->node === 'reviews'){
      $rq = $this->rq->shift();
      $reviewsBlockCfg['contentType'] = 'json';
      $bReviews = new user_reviewsList($rq, $reviewsBlockCfg);
      return $bReviews->route();
    }

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this->rq));

    $this->addItems(array(

      'PANEL_PROFILE_INFO' => new profile_publicViewStats($this, array('U' => $this->U)),

      'PANEL_PROFILE_REVIEWS' => new page_coloredPanel($this, array(
        'items' => array(new user_reviewsList($this->rq, $reviewsBlockCfg)),
        'title' => \Verba\Lang::get('profile reviews panelTitle'),
        'scheme' => 'green',
      ))
    ));

    return $this;

  }

}
?>