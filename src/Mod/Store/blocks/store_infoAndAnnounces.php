<?php
class store_infoAndAnnounces extends \Verba\Block\Html {

  public $templates = array(
    'content' => 'store/infoAndAnnounces/content.tpl',
    'title' => 'store/infoAndAnnounces/title.tpl',
    'body' => 'store/infoAndAnnounces/body.tpl',
    'note' => 'store/infoAndAnnounces/note.tpl',
  );

  public $tplvars = array(
    'BODY' => '',
    'TITLE' => '',
    'STORE_NOTES' => '',
  );
  /**
   * @var \Model\Store
   */
  public $Store;

  function build(){

    $this->content = '';

    if(!$this->Store || !$this->Store instanceof \Model\Store){
      return $this->content;
    }

    $_store = \Verba\_oh('store');

    // Ава и Название магазина
    \Verba\_mod('image');

    if(strlen($this->Store->picture)){
      $iCfg = \Mod\Image::getImageConfig($_store->p('picture_config'));
    }
    if(isset($iCfg)){
      $picUrl = $iCfg->getFullUrl(basename($this->Store->picture), 'ico80');
    }else{
      $picUrl = '/images/store/no-pic.jpg';
    }

    $this->tpl->assign(array(
      'STORE_PIC_URL' => $picUrl,
      'STORE_NAME' => htmlspecialchars($this->Store->title),
      'STORE_INFO_URL' => \Mod\Store::getInstance()->getPublicUrl($this->Store->id,'info'),
      'ONLINE_STATUS_SIGN' => $this->Store->getOnlineStatus()
    ));

    $this->tpl->parse('STORE_AVATAR_AND_TITLE', 'title');

    // Статистика магазина
    $_order = \Verba\_oh('order');

    $qm = new \Verba\QueryMaker($_order, false, array('created'));
    $qm->addSelectProp('SQL_CALC_FOUND_ROWS');
    $qm->addWhere(25, 'status');
    $qm->addWhere($this->Store->getId(), 'storeId');
    $qm->addOrder(array($_order->getPAC() => 'd'));
    $qm->addLimit(1);

    $sqlr = $qm->run();
    $q = $qm->getQuery();
    if($sqlr && $sqlr->getNumRows()){
      $row = $sqlr->fetchRow();
      $this->tpl->assign(array(
        'STORE_COMPLETED_ORDERS_COUNT' => (int)$sqlr->SQL_CALC_FOUND_ROWS,
        'STORE_LAST_COMPLETED_ORDER_DATE' => date('d.m.y', strtotime($row['created'])),
      ));
    }else{
      $this->tpl->assign(array(
        'STORE_COMPLETED_ORDERS_COUNT' => '-',
        'STORE_LAST_COMPLETED_ORDER_DATE' => '-',
      ));
    }

    $this->tpl->assign(array(
      'STORE_REGISTERED_DATE' => date('d.m.y', strtotime($this->Store->created)),
      'STORE_RATING' => $this->Store->rating,
    ));

    $this->tpl->parse('STORE_STATS', 'body');

    // Новости магазина
    $_news = \Verba\_oh('news');
    $qm = new \Verba\QueryMaker($_news);
    $qm->addConditionByLinkedOT($_store, $this->Store->getId());
    $qm->addWhere(1, 'active');
    $qm->addOrder(array('priority' => 'd', $_store->getPAC() => 'd'));

    $sqlr = $qm->run();
    if($sqlr && $sqlr->getNumRows()){

      while($row = $sqlr->fetchRow()){
        $this->tpl->assign(array(
          'NEWS_TEXT' => htmlspecialchars($row['text']),
        ));
        $this->tpl->parse('STORE_NOTES', 'note', true);
      }
    }

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>