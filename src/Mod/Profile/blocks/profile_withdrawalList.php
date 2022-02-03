<?php
class profile_withdrawalList extends \Verba\Block\Json{

  public $userId;

  public $titleLangKey;


  function build(){

    if(!$this->userId){
      throw  new \Verba\Exception\Building('Unknown object');
    }

    $_wdr = \Verba\_oh('withdrawal');
    $dcfg = array();
    $cfg = array(
      'cfg' => 'public public/profile/withdrawal',
      'dcfg' => $dcfg,
      'block' => $this,
    );

    $list = $_wdr->initList($cfg);

    $qm = $list->QM();
    $qm->addWhere($this->userId, 'owner');

//    $this->tpl()->assign(array(
//      'WITHDRAWAL_LIST' => $list->generateList(),
//    ));

    $q = $list->QM()->getQuery();

    //$this->content = $this->tpl->parse(false, 'content');
    $this->content = $list->generateList();

    return $this->content;

  }

}
?>
