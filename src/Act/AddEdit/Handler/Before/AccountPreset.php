<?php
namespace Verba\Act\AddEdit\Handler\Before;

use \Verba\Act\AddEdit\Handler\Before;

class AccountPreset extends Before {

  //protected $allowed = array('new');
    protected $_allowedEdit = false;

  function run(){
    try {
      $active = null;

      $ownerId = $this->ah->getTempValue('owner');
      $currencyId = $this->ah->getTempValue('currencyId');
      $_store = \Verba\_oh('store');
      $qm = new \Verba\QueryMaker($_store, false, array('currencyId'));
      $qm->addWhere($ownerId, 'owner');
      $qm->addWhere(1, 'active');
      $qm->addLimit(1);
      $sqlr = $qm->run();
      if($sqlr && $sqlr->getNumRows()){
        $store = $sqlr->fetchRow();
        $storeCurId = $store['currencyId'];
      }

      if(isset($storeCurId) && $storeCurId == $currencyId){
        $active = 1;
      }

      if($active === null){
        $cart = \Verba\_mod('cart');
        $cartCurId = $cart->getCurrencyId();
        if($cartCurId && $cartCurId == $currencyId){
          $active = 1;
        }
      }

      if($active != 1){
        $active = 0;
      }

      $data = array(
        'active' => $active,
      );

      $this->ah->setGettedData($data);

    }catch (\Exception $e){
      $this->ah->log()->error($e);
      return false;
    }

    return true;
  }
}
