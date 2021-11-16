<?php
namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class OType extends After
{
  //protected $allowed = array('new');
    protected $_allowedEdit = false;

  function run(){

    $sepvlt = (bool)$this->ah->getGettedValue('separate_vault');
    $base = $this->ah->getObjectValue('base');

    if(!$sepvlt || !$base){
      return;
    }
    $code = $this->ah->getObjectValue('ot_code');
    $ot_id = $this->ah->getIID();
    $_base = \Verba\_oh($base);

    $newVltName = $code;

    $modOtype = \Mod\Otype::getInstance();

    $cloned_vlt_id = $modOtype->cloneVault($_base, $newVltName);
    if(!$cloned_vlt_id){
      $this->log()->error('Unable to clone OT Vault $_base: '.$_base->getCode().', this_ot: '.var_export($ot_id, true).'['.var_export($code, true).']');
      return false;
    }
    $_ot = \Verba\_oh('otype');
    $ot_upd = $_ot->initAddEdit(array('action' => 'edit'));
    $ot_upd->setIID($ot_id);
    $ot_upd->setGettedObjectData(array('vlt_id' => $cloned_vlt_id));
    $ot_upd->addedit_object();

    \Verba\_mod('system')->planeClearCache();

    return true;
  }
}
