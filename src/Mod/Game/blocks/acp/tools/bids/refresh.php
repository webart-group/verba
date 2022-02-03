<?php
namespace Mod\Game\blocks\acp\tools\bids;

class refresh extends \Verba\Block\Json{

  protected $_handledCount = 0;

  function build()
  {
    set_time_limit(3600);
    $this->handleOT('loo');
    $this->content = 'Обработано товаров: '.$this->_handledCount;
    return $this->content;
  }

  function handleOT($ot){
    $oh = \Verba\_oh($ot);
    if(!$oh){
      return false;
    }
    $pac = $oh->getPAC();
    $qm = new \Verba\QueryMaker($oh, false, true, true);
    $q = $qm->getQuery();

    $start = 0;
    $step = 100;

    do{
      $fullq = $q."\n".' LIMIT '.$start.','.$step;
      $sqlr = $this->DB()->query($fullq);
      if(!$sqlr || !$sqlr->getNumRows()) {
        break;
      }

      while($row = $sqlr->fetchRow()){

        $ae = $oh->initAddEdit(array(
          'action' => 'edit',
          'iid' => $row[$pac],
        ));
        $ae->setActualItem($row);
        $ae->setIgnoreErrors(true);
        $ae->setGettedObjectData(array(
          $pac => $row[$pac]
        ));
        $ae->addedit_object();
        $this->_handledCount++;
      }
      $start += $step;

    }while($sqlr && $sqlr->getNumRows());

    $directDescendantes = $oh->getDescendants(false);
    if(is_array($directDescendantes) && count($directDescendantes)){
      foreach ($directDescendantes as $did){
        $this->handleOT($did);
      }
    }
    return true;
  }

  function getToolE(){
    return new refreshE($this);
  }

}

class refreshE extends \page_eInteractive{

  public $eid = 'acp_tool_bids_refresh';

  public $classes = 'tool-bids_refresh';
  public $group = 'bids_refresh';
  public $ui = array(
      'attr' => array(
        'data-url' => '/acp/tools/game/bids/refresh'
      )
  );

  function init()
  {
    $this->ui['value'] =  \Verba\Lang::get('game acp tools refresh button');
  }

}

?>