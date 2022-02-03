<?php
class comment_publicList extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'comment/list/wrap.tpl',
    'item' => 'comment/list/item.tpl',
    'empty' => 'comment/list/empty.tpl'
  );

  function build(){
    $_comm = \Verba\_oh('comment');
    $qm = new \Verba\QueryMaker($_comm, false, true);
    $qm->addWhere(1, 'active');
    $qm->addOrder(array($_comm->getPAC() => 'd'));
    $qm->addConditionByLinkedOT(\Verba\_oh($this->request->ot_id), $this->request->iid);
    $q = $qm->getQuery();
    $sqlr = $qm->run();

    if($sqlr && $sqlr->getNumRows()){
      while($row = $sqlr->fetchRow()){
        $time = strtotime($row['created']);
        $mname = \Verba\Lang::get('date m '.date('n',$time));
        $this->tpl->assign(array(
          'ITEM_DATE' => date('d '.$mname.' Y', $time),
          'ITEM_NAME' => htmlspecialchars($row['name']),
          'ITEM_COMMENT' => htmlspecialchars($row['comment']),
        ));
        $this->tpl->parse('COMMENTS_ITEMS', 'item', true);
      }
    }else{
      $this->tpl->parse('COMMENTS_ITEMS', 'empty');
    }
    $this->addCSS(array('comments'));

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>