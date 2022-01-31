<?php

namespace Verba\Model;

class Store extends Item {

    protected $otype = 'store';
    /**
     * @var \Verba\User\Model\User
     */
    private $User ;

    /**
     * @var array
     *
     * В ключе массива - id валюты ,
     * значение - массив minPc для input-платежек" paysysId -> minPc
     */
    private $_pc = [];

    use LastActivityStatus;

//  function loadData($iid)
//  {
//
//    $_user = \Verba\_oh('user');
//    $qm = new QueryMaker($this->oh);
//    list($ua) = $qm->createAlias($_user->vltT());
//    list($a) = $qm->createAlias();
//    $qm->addCJoin(array(array('a' => $ua)),
//      array(
//        array(
//          'p' => array('a'=> $a, 'f' => 'owner'),
//          's' => array('a'=> $ua, 'f' => $_user->getPAC()),
//        ),
//      ), true
//    );
//
//    $qm->addSelectPastFrom('last_activity', $ua);
//    $qm->addWhere($iid, $this->oh->getPAC());
//    $sqlr = $qm->run();
//    if(!$sqlr || !$sqlr->getNumRows()){
//      return false;
//    }
//    return $sqlr->fetchRow();
//  }

    function getOperatorEmail(){
        $this->getUser();
        if(!$this->User){
            return false;
        }
        return $this->User->email;
    }

    function getUser(){
        if($this->User === null){
            $User = new \Verba\User\Model\User($this->owner);
            $this->User = $User && $User instanceof \Verba\User\Model\User ? $User : false;
        }
        return $this->User;
    }
    /**
     * @param $Cur \Verba\Model\Currency|integer
     * @return array|bool Возвращает массив
     */
    function getPcDataByCurrency($Cur){
        if(!$Cur instanceof \Verba\Model\Currency){
            $Cur =  \Mod\Currency::getInstance()->getCurrency($Cur);
        }

        if(!$Cur instanceof \Verba\Model\Currency){
            return false;
        }
        $iCurId = $Cur->getId();
        if (!array_key_exists($iCurId, $this->_pc)) {
            if(!$this->loadPaysysPcPairsByCurrency($Cur)){
                return false;
            }
        }

        return $this->_pc[$iCurId];
    }

    /**
     * @param $currArg \Verba\Model\Currency|integer
     * @return array|null Возвращает массив paysysId -> minPc для выбранной валюты
     */
    function getPaysysPcPairsByCurrency($currArg){

        if($currArg instanceof \Verba\Model\Currency){
            $curr = $currArg;
        }elseif($currArg){
            $curr =  \Mod\Currency::getInstance()->getCurrency($currArg, true);
        }

        if (!isset($curr)) {
            return null;
        }
        $iCurId = $curr->getId();
        if (!array_key_exists($iCurId, $this->_pc)) {
            if(!$this->loadPaysysPcPairsByCurrency($curr)){
                return array();
            }
        }

        // Если у торговца включена валюта оплаты,
        // возвращаем список платежек для нее
        if(array_key_exists($iCurId, $this->_pc[$iCurId])){
            return $this->_pc[$iCurId][$iCurId]['paysys'];
        }

        // Валюта вывода (торговца) с наивысшим приоритетом вывода
        $oCurHigherRatingOut = current($this->_pc[$iCurId]);

        return $oCurHigherRatingOut['paysys'];
    }

    /**
     * Для переданной Валюты Ввода, находит подходящую валюту вывода и
     * возвращает массив пар (платежкаId => Pc [,....])
     * отсортированный понаименьшему Pc
     *
     * @param $Cur \Verba\Model\Currency Валюта ввода средств
     * @return bool
     */
    private function loadPaysysPcPairsByCurrency($Cur){
        $iCurId = $Cur->getId();
        if(!array_key_exists($iCurId, $this->_pc)){
            $this->_pc[$iCurId] = array();
        }

        /**
         * @var $mStore Store
         */
        $mStore = \Mod\Store::getInstance();
        /**
         * @var $mShop Shop
         */
        $mShop = \Mod\Shop::getInstance();

        $_cur = \Verba\_oh('currency');
        // $Cur - Валюта ввода средств
        // Формируем список платежек доступных на ввод
        $psIids = array_keys($Cur->getPaysysByLinkRule('input'));

        if(!$psIids){
            return false;
        }

        $_acc = \Verba\_oh('account');

        $q = "SELECT 
      cpk.iCurId 
      , cpk.oCurId
      , c2.ratingOut AS oCurRatingOut
      , cpk.iPaysysId 
      , cpk.Ex 
      , cpk.balPers
      , cpk.Pc 
      , cpk.Pck 

    FROM `".SYS_DATABASE."`.`".$mShop->cppr_table."` cpk 
    
    RIGHT JOIN ".$_acc->vltURI()." a 
      ON a.currencyId = cpk.oCurId
    
    LEFT JOIN ".$_cur->vltURI()." AS `c2` 
      ON c2.id = cpk.oCurId
          
    WHERE 
      a.owner = ".$this->owner." && a.mode = 1158
      AND cpk.iCurId = ".$Cur->getId()."
      AND cpk.iPaysysId IN (".implode(",",$psIids).")
      AND cpk.active = 1
   ORDER BY iCurId, oCurRatingOut DESC, cpk.oCurId, cpk.Pc
   
    ";

        $sqlr = $this->DB()->query($q);

        if(!$sqlr || !$sqlr->getNumRows()){
            return false;
        }

        // исключаем внутренние кошельки
        $internalPaysysIds = array_flip(\Verba\_mod('payment')->getInternalPaysysIds());

        while($row = $sqlr->fetchRow()){

            $row['iCurId'] = (int)$row['iCurId'];
            $row['oCurRatingOut'] = (int)$row['oCurRatingOut'];
            $row['oCurId'] = (int)$row['oCurId'];
            $row['Pc'] = (float)$row['Pc'];
            $row['Pck'] = (float)$row['Pck'];
            $row['iPaysysId'] = (int)$row['iPaysysId'];
            $row['Ex'] = (float)$row['Ex'];
            $row['balPers'] = (float)$row['balPers'];

            if(!array_key_exists($row['oCurId'], $this->_pc[$iCurId])){
                $this->_pc[$iCurId][$row['oCurId']] = array(
                    'paysys' => array(),
                    'PcMin' => $row['Pc'],
                    'PcMinPsId' => $row['iPaysysId'],
                    'PcMinExt' => false,
                    'PcMinExtPsId' => false,
                    'oCurRatingOut' => $row['oCurRatingOut']
                );
            }
            // фиксируем минимальный Pc для невнутренней платежной системы
            if($this->_pc[$iCurId][$row['oCurId']]['PcMinExt'] === false
                && !array_key_exists($row['iPaysysId'], $internalPaysysIds))
            {
                $this->_pc[$iCurId][$row['oCurId']]['PcMinExt'] = $row['Pc'];
                $this->_pc[$iCurId][$row['oCurId']]['PcMinExtPsId'] = $row['iPaysysId'];
            }

            $this->_pc[$iCurId][$row['oCurId']]['paysys'][$row['iPaysysId']] = array(
                'Pc' => $row['Pc'],
                'Ex' => $row['Ex'],
                'Pck' => $row['Pck'],
                'balPers' => $row['balPers'],
            );
        }

        reset($this->_pc[$iCurId]);

        return true;
    }

    public function calcPriceForBuyer($price, $currency, $paysysId){

        $ps_pcs = $this->getPaysysPcPairsByCurrency($currency);

        if(!$ps_pcs || !array_key_exists($paysysId, $ps_pcs)
            || !array_key_exists('Pc', $ps_pcs[$paysysId])
            || !is_float($ps_pcs[$paysysId]['Pc'])
        ){
            return $price;
        }

        $price = $price * $ps_pcs[$paysysId]['Pc'];
        return \Verba\reductionToCurrency($price);
    }

    public function getUrlBase($subaction = false){
        return \Mod\Store::getInstance()->getPublicUrl($this->getId(), $subaction);
    }

    public function getAccounts(){
        return $this->getUser()->Accounts()->getAccounts();
    }

    function getAccountByCur($currencyId){
        $accs = $this->getAccounts();
        /**
         * @var $cAcc Mod\Account\Model\Account
         */
        foreach($accs as $cAcc){
            if($cAcc->getRawValue('currencyId') == $currencyId){
                return $cAcc;
            }
        }
        return false;
    }

    function getPcOutData($iCurId, $iPaysysId){

        $pc_data = $this->getPcDataByCurrency($iCurId);

        if(!is_array($pc_data) || !count($pc_data) || !$iPaysysId)
        {
            return false;
        }

        if(array_key_exists($iCurId, $pc_data)){
            $selectedCurOut = $pc_data[$iCurId];
            $oCurId = $iCurId;
        }else{
            reset($pc_data);
            $selectedCurOut = current($pc_data);
            $oCurId = key($pc_data);
        }

        if(!array_key_exists($iPaysysId, $selectedCurOut['paysys'])){
            return false;
        }

        return $selectedCurOut['paysys'][$iPaysysId] + array('oCurId' => $oCurId);
    }

}