<?php
namespace Verba\User\RequestHandler;

class ReviewsList extends user_contentList {

  public $css = array('reviews');

  public $otype = 'review';
  public $cfg = 'public public/reviews/store public/reviews/user';
  public $urlBase;

  public function init(){

    parent::init();

    $Url = new \Verba\Url($this->urlBase);
    $Url->shiftPath('list');
    $this->dcfg['url']['forward'] = $Url->get();

  }

}
?>