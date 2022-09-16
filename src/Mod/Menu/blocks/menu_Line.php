<?php
class menu_Line extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/menu/page-inner/wrap.tpl',
    'item' => '/menu/page-inner/item.tpl',
  );

  public $rootId = null;
  public $rootAlias = null;
  public $order = array('priority' => 'd');

  public $lastItem;
  public $lastItemIsCurrent = false;

  public $frash = false;

  public $currentId;

  function prepare(){
    $this->fire('beforePrepare');
    /**
     * @var $mMenu Menu
     */
    $mMenu = \Verba\_mod('menu');

    if($this->lastItem === null){
      $this->lastItem = $mMenu->getActiveNode();
      if($this->lastItem['url'] == $this->rq->uf_str){
        $this->lastItemIsCurrent = true;
      }
    }
    $this->fire('afterPrepare');
  }

  function build(){

    $this->content = '';

    $_menu = \Verba\_oh('menu');

    $this->currentId = is_array($this->lastItem) && !empty($this->lastItem)
      ? $this->lastItem[$_menu->getPAC()]
      : false;

    $QM = new \Verba\QueryMaker($_menu, false, true);
    $cnd = $QM->addConditionByLinkedOT($_menu, $this->rootId);
    $cnd->setRelation(2);
    $QM->addWhere(1, 'active');
    $QM->addOrder($this->order);


    $sqlr = $QM->run();
    if(!$sqlr || !$sqlr->getNumRows()){
      return '';
    }

    $this->tpl->clear_vars(array('ITEMS'));
    $U = \Verba\User();
    $i = 0;
    while($row = $sqlr->fetchRow()){
      if(!$U->chr($row['key_id'])){
        continue;
      }
      $i++;
      $this->assignItem($row);
      $this->tpl->parse('CONTENT', 'item', true);
    }
    if(!$i){
      return $this->content;
    }
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

  function assignItem($row){
    $this->tpl->assign(array(
      'URL' => $row['url'],
      'TITLE' => $row['title'],
      'CSS_CLASS' => $row['css_class'],
      'SELECTED_CLASS_SIGN' => $row['id'] == $this->currentId ? ' selected' : '',
    ));
  }

}
?>