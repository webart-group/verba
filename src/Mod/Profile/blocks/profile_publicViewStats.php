<?php
class profile_publicViewStats extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'profile/pubview/stats/content.tpl',
    'title' => 'profile/pubview/stats/title.tpl',
    'body' => 'profile/pubview/stats/body.tpl'
  );

  public $tplvars = array(
    'PROFILE_PIC_URL' => '',
    'PROFILE_DISPLAY_NAME' => '',
    'PROFILE_RATING' => '',
    'PROFILE_PUB_URL' => '',
    'PROFILE_REGISTERED_DATE' => '',
    'PROFILE_PAYED_ORDERS_COUNT' => '0',
    'PROFILE_LAST_PAYED_ORDER_DATE' => '',
  );

  /**
   * @var $U \Verba\User\Model\User
   */
  public $U;


  function init(){
    if(!is_object($this->U) || !$this->U instanceof \Verba\User\Model\User
  || !$this->U->getID()){
      throw new \Exception\Routing();
    }
  }

  function build(){

    // TITLE
    \Verba\_mod('image');
    $picUrl = $this->U->makeUserpic(80);

    if(!$picUrl){
      $picUrl = '/images/profile/no-pic.jpg';
    }

    $this->tpl->assign(array(
      'PROFILE_PIC_URL' => $picUrl,
      'PROFILE_DISPLAY_NAME' => htmlspecialchars($this->U->display_name),
      'PROFILE_PUB_URL' => \Mod\Profile::getInstance()->getPublicUrl($this->U->getID()),
      'PROFILE_RATING' => 100,
      'ONLINE_STATUS_SIGN' => $this->U->getOnlineStatus()
    ));

    $this->tpl->parse('PROFILE_AVATAR_TITLE', 'title');


    // BODY
    $_user = \Verba\_oh('user');

    $_order = \Verba\_oh('order');

    $qm = new \Verba\QueryMaker($_order, false, array('created'));
    $qm->addSelectProp('SQL_CALC_FOUND_ROWS');
    $qm->addWhere(1, 'payed');
    $qm->addWhere($this->U->getID(), 'owner');
    $qm->addOrder(array($_order->getPAC() => 'd'));
    $qm->addLimit(1);

    $sqlr = $qm->run();
    $q = $qm->getQuery();
    if($sqlr && $sqlr->getNumRows()){
      $row = $sqlr->fetchRow();
      $this->tpl->assign(array(
        'PROFILE_PAYED_ORDERS_COUNT' => (int)$sqlr->SQL_CALC_FOUND_ROWS,
        'PROFILE_LAST_PAYED_ORDER_DATE' => date('d.m.y', strtotime($row['created'])),
      ));
    }

    $this->tpl->assign(array(
      'PROFILE_REGISTERED_DATE' => date('d.m.y', strtotime($this->U->created)),
    ));

    $this->tpl->parse('PROFILE_STATS', 'body');

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>