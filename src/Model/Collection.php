<?php
namespace Verba\Model;


class Collection extends \Verba\Configurable {
    /**
     * @var array OTI items
     */
    protected $items = array();
    protected $ot = '';
    /**
     * @var \Verba\Model
     */
    protected $oh;

    function __construct($ot, $cfg = array()){
        $this->ot = $ot;
        $this->getOh();

        $this->applyConfigDirect($cfg);
    }

    function getOh(){
        if($this->oh === null){
            $this->oh = \Verba\_oh($this->ot);
        }
        return $this->oh;
    }

    function getItem($iid){
        $r = $this->getItems(array($iid));
        return isset($r[$iid]) && is_object($r[$iid]) ? $r[$iid] : false;
    }

    function getItems($iids){
        $r = array();
        if(!\Verba\convertToIdList($iids, true)){
            return $r;
        }
        $toLoad = array_diff($iids, array_keys($this->items));
        if(count($toLoad)){
            $this->loadItems($toLoad);
        }

        $this->getOh();
        //$pac = $this->oh->getPAC();

        foreach($iids as $k){
            if(!array_key_exists($k, $this->items)){
                $r[$k] = null;
                continue;
            }
            $r[$k] = $this->items[$k];
        }

        return $r;
    }

    protected function loadItems($iids){
        $this->getOh();

        $items = $this->oh->getData($iids, true);
        if(!$items){
            return $items;
        }
        $r = array();
        foreach($items as $iid => $cItm){
            $r[$iid] = $this->addItem($iid, $cItm);
        }
        return $items;
    }

    protected function addItem($iid, $item){
        $this->items[$iid] = false;
        $this->getOh();

        $this->items[$iid] = $this->oh->initItem($item);

        if(!is_object($this->items[$iid]) || !$this->items[$iid]->getId())
        {
            $this->items[$iid] = false;
        }

        if($this->oh->getStringPAC()) {
            $this->items[$item[$this->oh->getStringPAC()]] = $this->items[$iid];
        }

        return $this->items[$iid];
    }

}
