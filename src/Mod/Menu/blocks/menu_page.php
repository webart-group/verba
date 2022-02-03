<?php
class menu_page extends \Verba\Block\Html{

  /**
   * Если к пункту меню не привязаны тексты и параметр == true
   * будет выполнен поиск контента по коду из последнего фрагмента url
   * @var bool
   */
  public $searchContentByCode = false;

  /**
   * Меню id
   *
   * @var integer
   */
  public $nodeId;

  public $cntTitleCssClassFirstItem = 'f-entry';
  public $cntTitleCssClassOtherItems = 'nf-entry';

  function prepare(){

    if(!$this->nodeId){
      /**
       * @var $mMenu Menu
       */
      $mMenu = \Verba\_mod('menu');
      $this->nodeId = $mMenu->getActiveNode(false);
    }

  }

  function build(){

    if(!$this->nodeId){
      // 404
      throw new \Exception\Routing();
    }

    $items = array();
    $_cnt = \Verba\_oh('content');

    $_menu = \Verba\_oh('menu');
    $qm = new \Verba\QueryMaker($_cnt, false, true);
    $qm->addConditionByLinkedOT($_menu, $this->nodeId);
    $qm->addOrder(array('priority' => 'd'));
    $qm->addWhere(1, 'active');
    $sqlr = $this->DB()->query($qm->getQuery());
    if($sqlr && $sqlr->getNumRows()){
      while($row = $sqlr->fetchRow()){
        $items[$row[$_cnt->getPAC()]] = $row;
      }
    }


    if(!count($items) && $this->searchContentByCode){
      $cnt_id = $this->request->uf[count($this->request->uf) - 1];
      $row = $_cnt->getData($cnt_id, 1);
      if($row['active']){
        $items[$row[$_cnt->getPAC()]] = $row;
      }
    }

    if(!count($items)){
      return $this->content;
    }
    $isFirstItem = true;
    $b = new content_getBlock($this);
    foreach($items as $id => $item){
      $b->titleClass = $isFirstItem ? $this->cntTitleCssClassFirstItem : $this->cntTitleCssClassOtherItems;
      $b->text = $item['text'];
      $b->title = $item['title'];
      $b->extra_css_class = $item['extra_css_class'];
      $b->id = $id;
      $this->content .= $b->build();
      $isFirstItem = false;
    }

    return $this->content;
  }
}
?>