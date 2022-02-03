<?php

namespace Mod;

class Content extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $otic_ot = 'content';

    protected $substEmptyIIdAsIndex = true;

    /*
      function addEditNow($bp = null, $data = false){
        $oh = \Verba\_oh('content');
        list($action, $faction) = \Act\AddEditHandlers::extractAEFActionsFromURL($bp['action']);
        $iid  = (int)$this->extractID($bp);
        $data = is_array($data)
              ? $data
              : $_REQUEST['NewObject'][$oh->getID()];
        try{
          $ae = $oh->initAddEdit(array(
            'action' => $action,
            'key_id' => $oh->getBaseKey(),
            'iid' => $iid,
          ));

          if(isset($bp['pot'])){
            $ae->addMultipleParents($bp['pot']);
          }
          $ae->setGettedObjectData($data);
          $r = $ae->addedit_object();
        }catch(Exception $e){
          return $e;
        }
        return $ae;
      }

      function addEditNowResult($BParams = null, $objData = false, $muob = false){
        $result = $this->addEditNow($BParams, $objData, $muob);
          $r['data']  = !isset($result['problems'])
                      ?  Lang::get('aenow success msg')
                      :  Lang::get('aenow fault msg');
          return $r;
      }

      function deleteNow($BParams = null){
        $BParams['ot_id'] = \Verba\_oh('content')->getID();
        $BParams['action'] = 'delete';
        parent::deleteNow($BParams);
        resultReport(false, null);
      }
    */
    function issetFieldValue($oh, $field, $value)
    {
        $field = (string)$field;
        $value = (string)$value;
        $oh = \Verba\_oh($oh);
        $qm = new \Verba\QueryMaker($oh->getID(), $oh->getBaseKey(), array($field));
        $qm->addWhere("`$field` = '" . $this->DB()->escape_string(trim($value)) . "'");
        $qm->addLimit(1);
        $qm->makeQuery();
        $oRes = $this->DB()->query($qm->getQuery());

        if ($oRes->getNumRows() == 1) {
            return true;
        }
        return false;
    }

}
