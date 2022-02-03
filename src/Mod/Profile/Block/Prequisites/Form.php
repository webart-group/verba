<?php

namespace Mod\Profile\Block\Prequisites;

class Form extends \Verba\Block\Json{

  protected $curId;
  protected $paysysId;
  /**
   * @var \Verba\Model\Currency
   */
  protected $cur;
  protected $Paysys;

  protected $itemData;

  protected $action;


  function prepare(){

    $_oh = \Verba\_oh('prequisite');

    if($this->rq->ot_id != $_oh->getID()){
      throw  new \Verba\Exception\Building('Unknown otype');
    }

    if($this->rq->iid){ // edit
      $this->action = 'edit';
      $this->itemData = $_oh->getData($this->rq->iid, 1);
      if(!$this->itemData){
        throw  new \Verba\Exception\Building('Item not found');
      }

      $this->curId = $this->itemData['currencyId'];
      $this->paysysId = $this->itemData['paysysId'];


    }else{ // new
      $this->action = 'new';
      $this->curId = $this->rq->getParam('currencyId', true);
      $this->paysysId = $this->rq->getParam('paysysId', true);
    }

    if(!$this->curId || !$this->paysysId){
      throw  new \Verba\Exception\Building('Bad paysys or currency');
    }



    $mCur = \Verba\_mod('currency');

    $this->cur = $mCur->getCurrency($this->curId);
    $this->Paysys = \Verba\_mod('payment')->getPaysys($this->paysysId);
    if($this->action == 'new'){
      if (!$this->cur || !$this->cur->active) {
        throw  new \Verba\Exception\Building('Bad currency');
      }

      if (!$this->Paysys
        || !$this->cur->isPaysysLinkExists($this->paysysId, 'output')
        || !$this->Paysys->active
      ) {
        throw  new \Verba\Exception\Building('Bad paysys');
      }
    }


  }

  function build(){

    $this->content = false;

    $_oh = \Verba\_oh('prequisite');

    $cfg = $this->request->asArray();

    $cfg['cfg'] = 'public public/profile/prequisite';
    $cfg['block'] = $this;
    $dcfg = array(
      'fields' => array(
        'currencyId' =>  array(
          'value' => $this->curId,
        ),
        'paysysId' =>  array(
          'value' => $this->paysysId,
        ),
      ),
    );

    $cfg['dcfg'] = $dcfg;

    $form = $_oh->initForm($cfg);

    if($form->getAction() == 'edit'){
      $form->setExistsValues($this->itemData);
    }

    $form->tpl()->assign(array(
      'PAYSYS_DESCRIPTION' => $this->Paysys->account_description,
      'PAYSYS_DESCRIPTION_SIGN' => !empty($this->Paysys->account_description) ? '' : ' hidden',

    ));
    $title = \Verba\Lang::get('prequisite form title ' . $form->getAction(),
      array(
        'paysysTitle' => is_object($this->Paysys) ? $this->Paysys->title : '??',
        'currencyCode' => is_object($this->cur) ? strtoupper($this->cur->code) : '??',
      )
    );

    $form->setTitleValue($title);

    $this->content = $form->makeForm();

    return $this->content;
  }
}
?>
