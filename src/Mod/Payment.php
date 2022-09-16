<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 22:20
 */

namespace Verba\Mod;

class Payment extends \Verba\Mod {

    use \Verba\ModInstance;

    protected $paysys;

    protected $cacheFile = 'paysystems';

    private $_paysys_internal = array();

    protected $_pp;

    function getPaysys($paysysId = false, $onlyActive = false, $checkRights = false){
        $onlyActive = (bool)$onlyActive;
        $checkRights = (bool)$checkRights;

        if($this->paysys === null && !$this->loadPaysystems()){
            return false;
        }
        if(!$paysysId){
            $r = array();
            foreach($this->paysys as $pId => $psys){
                if($checkRights && !User()->chr($psys->key_id, array('s'))){
                    continue;
                }
                if(!$onlyActive || $psys->active){
                    $r[$pId] = $psys;
                }
            }
            return count($r) ? $r : false;
        }
        if(!is_numeric($paysysId)){
            $pscode = strtolower($paysysId);
            $paysysId = false;
            foreach($this->paysys as $psId => $psObj){
                if(strtolower($psObj->code) != $pscode){
                    continue;
                }
                $paysysId = $psId;
                break;
            }
        }
        if(!$paysysId || !array_key_exists($paysysId, $this->paysys)){
            $this->log()->error('Requested PaySys with Unknown Id:'.var_export($paysysId, true));
            return false;
        }
        if($onlyActive && $this->paysys[$paysysId]->active != 1){
            $this->log()->error('Requested PaySys is inactive. Id:'.var_export($paysysId, true));
            return false;
        }
        if($checkRights && !User()->chr($this->paysys[$paysysId]->key_id, array('s'))){
            return false;
        }

        return $this->paysys[$paysysId];
    }

    protected function loadPaysystems(){
        $cache = new \Verba\Cache($this->getCacheDir().'/'.$this->cacheFile);
        if($cache->validateDataCache(600)){
            $str = $cache->getAsRequire();
            $this->paysys = unserialize($str);
            return true;
        }

        $_pay = \Verba\_oh('paysys');
        $pac = $_pay->getPAC();

        $qm = new \Verba\QueryMaker($_pay, false, true);
        $qm->addOrder(array('priority' => 'd'));
        $sqlr = $qm->run();
        if(!$sqlr || !$sqlr->getNumRows()){
            $this->paysys = false;
            $this->log->error('Unable to obtain Shop paysystems');
            return false;
        }

        $iids = array();

        while($row = $sqlr->fetchRow()){

            $this->paysys[$row[$pac]] = new \Verba\Mod\Paysys\Model\Item($row);
            if($this->paysys[$row[$pac]]->isInternal){
                $this->_paysys_internal[$row[$pac]] = $this->paysys[$row[$pac]];
            }
            $iids[] = $row[$pac];

        }

        $_tb = \Verba\_oh('textblock');
        $qm = new \Verba\QueryMaker($_tb, false, true);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addConditionByLinkedOTRight($_pay, $iids);

        //$q = $qm->getQuery();

        $sqlr = $qm->run();

        if($sqlr && $sqlr->getNumRows()){
            $txt_pac = $_tb->getPAC();
            while($row = $sqlr->fetchRow()){
                $this->paysys[$row['psId']]->OTICTextes->addItem($row[$txt_pac], $row);
            }
        }

        $cache->writeDataToCache(serialize($this->paysys));
        return true;
    }

    public function getPaysysMod($paysysId, $onlyActive = false){
        $paysys = $this->getPaysys($paysysId, $onlyActive);
        if(!is_object($paysys)){
            return false;
        }

        $modCode = $paysys->module;
        if(!$modCode) {
            $modCode = $paysys->getCode();
        }

        $modName = '\\Mod\\Paysys\\'.ucfirst($modCode);
        if(class_exists($modName)) {
            $mod = call_user_func([$modName, 'getInstance']);
        } else {
            $this->log()->error('Unable to load Paysys Module: '.$modName);
            return false;
        }

        if(!isset($mod->pscode)){
            $mod->pscode = $paysys->getCode();
        }
        return $mod;
    }

    function resetCache(){
        $cache = new \Verba\Cache($this->getCacheDir().'/'.$this->cacheFile);
        $cache->remove();
    }

    function getModByCode($code) {
        if($this->paysys === null && !$this->loadPaysystems()){
            return false;
        }
        $code = strtolower($code);
        /**
         * @var $Ps PaysysItem
         */
        foreach ($this->paysys AS $psid => $Ps){
            if(strtolower($Ps->getCode()) == $code){
                return $this->getPaysysMod($Ps->getId());
            }
        }
        return false;
    }

//    function findOrderDataFromRequest(&$ct){
//        \Verba\reductionToArray($ct);
//        $allPs = $this->getPaysys();
//        if(!is_array($allPs) || !count($allPs)){
//            return false;
//        }
//        foreach($allPs as $cPs){
//            $cPsMode = $this->getPaysysMod($cPs->code);
//            $cPsMode->extractOrderDataFromRequest($ct);
//        }
//    }

//    function getPaysysSelectorItems(){
//        $pss = $this->getPaysys(null, true, true);
//        $currProps = array(
//            'id' => null,
//            'code' => null,
//            'title' => null,
//            'rate' => null,
//            'rate_val' => null,
//            'short' => null,
//            'symbol' => null,
//        );
//        $r = array();
//        foreach($pss as $psId => $cPs){
//            if(!count($cPs->currencies)){
//                continue;
//            }
//            $r[] = $cPs;
//            foreach($cPs->currencies as $cK => $cCur){
//                $cPs->currencies[$cK] = array_intersect_key($cCur, $currProps);
//            }
//        }
//        return $r;
//    }

    // links value handler
    function lkh_recountK($action, $acode, $val, $mid, $gettedData, $tempData, $existsData){

        $pratio = (float)(isset($gettedData['p_ratio']) && !empty($gettedData['p_ratio'])
            ? $gettedData['p_ratio']
            : (isset($existsData['p_ratio']) && !empty($existsData['p_ratio'])
                ? $existsData['p_ratio']
                : false));

        $sratio = (float)(isset($gettedData['ch_ratio']) && !empty($gettedData['ch_ratio'])
            ? $gettedData['ch_ratio']
            : (isset($existsData['ch_ratio']) && !empty($existsData['ch_ratio'])
                ? $existsData['ch_ratio']
                : false));

        if(!$pratio){
            $pratio = 1;
        }

        if(!$sratio){
            $sratio = 1;
        }

        $ps1 = $this->getPaysys($mid[1]);
        $ps2 = $this->getPaysys($mid[3]);

        $C = 100;

        $S = $C + $C * $ps2->tax_transaction / 100;
        $Sz = $S + $S * $ps2->tax_input/100;
        $O = $Sz / $sratio * $pratio;
        $Op = $O + $O * $ps1->tax_transaction/100;

        $val = $Op / $C;

        return $val;
    }

    function getInternalPaysys(){

        $this->getPaysys(false, true);

        return $this->_paysys_internal;

    }

    function getInternalPaysysIds(){

        $this->getPaysys(false, true);

        return array_keys($this->_paysys_internal);

    }

}
