<?php

namespace Mod;

class Currency extends \Verba\Mod
{

    use \Verba\ModInstance;

    protected $valid_objects = array('currency');
    protected $currencies;
    protected $_currencies_codes = array();
    /**
     * @var \Verba\Model\Currency
     */
    protected $baseCurrency;
    protected $cacheFile = 'currencies';

    protected $_cc;

    function init() {
        $this->extractBaseCurrency();
    }

    function makeAction($bp)
    {
        switch ($bp['action']) {
            default :
                $handler = null;
        }

        if (!$handler) {
            $handler = parent::makeAction($bp);
        }
        return $handler;
    }

    function getCurrencies($onlyActive = false, $onlyVisible = false)
    {
        return $this->getCurrency(false, $onlyActive, $onlyVisible);
    }

    /**
     * @param bool $currencyId
     * @param bool $onlyActive
     * @return \Verba\Model\Currency|array|bool
     */
    function getCurrency($currencyId = false, $onlyActive = false, $onlyVisible = false)
    {
        if ($this->currencies === null && !$this->loadCurrencies()) {
            return false;
        }
        $onlyActive = (bool)$onlyActive;
        $onlyVisible = (bool)$onlyVisible;

        if (!$currencyId) {
            if (!$onlyActive && !$onlyVisible) {
                return $this->currencies;
            }

            $r = array();

            foreach ($this->currencies as $cId => $cCur) {

                if ($onlyActive && !$cCur->active) {
                    continue;
                }
                if ($onlyVisible && $cCur->hidden) {
                    continue;
                }
                $r[$cId] = $cCur;
            }

            return count($r) ? $r : false;

        }
        // если передан код валюты
        if (!is_numeric($currencyId)) {
            $crcode = strtoupper(trim($currencyId));
            $currencyId = false;
            if (array_key_exists($crcode, $this->_currencies_codes)) {
                $currencyId = $this->_currencies_codes[$crcode];
            }

        }

        if (!$currencyId || !array_key_exists($currencyId, $this->currencies)) {
            $this->log()->error('Requested Currency with Unknown Id:' . var_export($currencyId, true));
            return false;
        }
        if ($onlyActive && $this->currencies[$currencyId]->active != 1) {
            return false;
        }

        if ($onlyVisible && $this->currencies[$currencyId]->hidden == 1) {
            return false;
        }
        return $this->currencies[$currencyId];
    }

    protected function loadCurrencies()
    {
        $cache = new \Verba\Cache($this->getCacheDir() . '/' . $this->cacheFile);
        if ($cache->validateDataCache(84600)) {
            $this->currencies = unserialize($cache->getAsRequire());
            return true;
        }

        $_cur = \Verba\_oh('currency');
        $cur_ot_id = $_cur->getID();
        $_pay = \Verba\_oh('paysys');
        $pay_ot_id = $_pay->getID();

        $qm = new \Verba\QueryMaker($_cur, false, true);
        $qm->addOrder(array('priority' => 'd'));

        $sqlr = $qm->run();
        $iids = array();
        $pac = \Verba\_oh('currency')->getPAC();
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->currencies = false;
            $this->log->error('Unable to obtain Shop currencies');
            return false;
        }
        $this->currencies = array();
        while ($row = $sqlr->fetchRow()) {
            $this->currencies[$row[$pac]] = new \Verba\Model\Currency($row);
            $this->_currencies_codes[strtoupper($row['code'])] = $row[$pac];
            $iids[] = $row[$pac];
        }

        // Получение связей валюта-платежки на ввод и на вывод
        $rules = array();
        $rules['input'] = $_cur->getRule($_pay->getID(), 'input');
        $rules['output'] = $_cur->getRule($_pay->getID(), 'output');

        $q = array();
        foreach (array('input', 'output') as $ruleAlias) {
            $q[] = str_replace(array(
                0 => '#DB',
                1 => '#RULE',
            ), array(
                $rules[$ruleAlias]['uri'],
                $ruleAlias
            ), " SELECT * FROM #DB  
      WHERE `p_ot_id` = '" . $_cur->getID() . "' && `ch_ot_id` = '" . $_pay->getID() . "'
      && `rule_alias` = '#RULE' && `p_iid` IN ('" . implode("','", $iids) . "') 
      ");
        }

        $q = implode("\nUNION\n", $q);
        $io_links = $this->DB()->query($q);

        while ($row = $io_links->fetchRow()) {
            $ccurId = $row['p_iid'];
            $crule = $row['rule_alias'];
            $cpayid = $row['ch_iid'];
            if ($crule == 'output') {
                $row['kOut'] = $row['kIn'];
                unset($row['kIn']);
            }
            unset($row['p_ot_id'], $row['ch_ot_id'], $row['p_iid'], $row['ch_iid'], $row['rule_alias']);
            $this->currencies[$ccurId]->addPaysysLink($crule, $cpayid, $row);
        }

        $cache->writeDataToCache(serialize($this->currencies));
        return true;
    }

    protected function extractBaseCurrency()
    {
        if ($this->currencies === null && !$this->loadCurrencies()) {
            return false;
        }
        foreach ($this->currencies as $ccur) {
            if ($ccur->isbase == '1') {
                $def = $ccur;
                break;
            }
        }
        if (!isset($def)) {
            $this->log->error('Unable to detect Base Shop Currency');
            return false;
        }
        $this->baseCurrency = $def;
        return $this->baseCurrency;
    }

    function getBaseCurrency($field = null)
    {
        if (!$field || !is_object($this->baseCurrency)) {
            return $this->baseCurrency;
        }
        return $this->baseCurrency->p($field);
    }

    function resetCache()
    {
        $cache = new \Verba\Cache($this->getCacheDir() . '/' . $this->cacheFile);
        $cache->remove();
    }

    function convertToBase($price, $curId)
    {
        settype($price, 'float');
        if (!$price) {
            return 0;
        }
        $reqCur = $this->getCurrency($curId);
        $rate = $reqCur->p('rate_val');
        return $rate < 0
            ? $price / $rate
            : $price * $rate;
    }

    function convertFromBase($price, $curId)
    {
        settype($price, 'float');
        if (!$price) {
            return 0;
        }
        $reqCur = $this->getCurrency($curId);
        $rate = $reqCur->p('rate_val');
        return $rate < 0
            ? $price * $rate
            : $price / $rate;
    }

    function crossConvert($price, $exCurId, $newCurId)
    {
        return $this->convertFromBase($this->convertToBase($price, $exCurId), $newCurId);
    }

    function countOtPol($ex, $cur1_bratio, $cur2_bratio)
    {
        $pol = $cur1_bratio; //Курс первой валюты по отношению к базовой;
        $ot = $cur2_bratio + $cur2_bratio * $ex / 100;
        return array($ot, $pol);
    }

    function getCrossCurrencyProps()
    {

        if ($this->_cc !== null) {
            return $this->_cc;
        }

        $this->_cc = array();

        $_cur = \Verba\_oh('currency');

        $sqlr = $this->DB()->query("SELECT * FROM " . $_cur->vltURI($_cur));

        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }

        while ($row = $sqlr->fetchRow()) {
            if (!isset($this->_cc[$row['p_iid']])) {
                $this->_cc[$row['p_iid']] = array();
            }
            if (!isset($this->_cc[$row['p_iid']][$row['ch_iid']])) {
                $this->_cc[$row['p_iid']][$row['ch_iid']] = array();
            }
            $this->_cc[$row['p_iid']][$row['ch_iid']] = array(
                'ch_ratio' => $row['ch_ratio'],
                'p_ratio' => $row['p_ratio'],
                'ex' => (float)$row['ex'],
                'ot' => (float)$row['ot'],
                'pol' => (float)$row['pol'],
            );
        }

        return $this->_cc;
    }

    // links value handler
    function lkh_recountExPolOt($action, $acode, $val, $mid, $gettedData, &$tempData, $existsData, $pItem, $sItem)
    {

        if (!isset($gettedData['ex'])) {
            return null;
        }

        $ex = (float)(!empty($gettedData['ex'])
            ? $gettedData['ex']
            : 0);
        $pol = 0;
        $ot = 0;

        // получаем курсы пары валют к курсу базовой
        $_cur = \Verba\_oh('currency');
        $bc = $this->getBaseCurrency();

        $qe = "SELECT * FROM " . $_cur->vltURI($_cur) . "
WHERE
`p_ot_id` = '" . $_cur->getID() . "'
&& `p_iid` IN ('" . $mid[1] . "','" . $mid[3] . "')
&& `ch_ot_id` = '" . $_cur->getID() . "'
&& `ch_iid` = '" . $this->DB()->escape($bc->getId()) . "'
&& `rule_alias` = ''
&& `p_ratio` = '1'
";
        $sqlr = $this->DB()->query($qe);
        if ($sqlr && $sqlr->getNumRows()) {
            $curs = array(
                '1' => 1,
                '2' => 1,
            );
            while ($row = $sqlr->fetchRow()) {
                $i = $row['p_iid'] == $mid[1] ? '1' : '2';
                $curs[$i] = $row;
            }
            if ($mid[1] == $mid[3] && $sqlr->getNumRows()) {
                $curs[2] = $curs[1];
            }
            list($ot, $pol) = $this->countOtPol($ex, $curs['1']['ch_ratio'], $curs['2']['ch_ratio']);

        } else {
            $this->log()->error('Unable to calculate `Ot` and `Pol` attrs, currency rates not found. $mid: ' . var_export($mid, true));
            $ex = 0;
        }

        $tempData['pol'] = (float)$pol;
        $tempData['ot'] = (float)$ot;
        return $ex;
    }

    function lkh_handleBaseCurRateUpdated($action, $acode, $val, $mid, $gettedData, &$tempData, $existsData, $pItem, $sItem)
    {
        if ($val === null) {
            return null;
        }
        $baseCur = $this->getBaseCurrency();
        if ($mid[3] != $baseCur->getId() || $mid[1] == $baseCur->getId()) {
            return $val;
        }
        $val = reductionToFloat($val);
        if (!$val) {
            return $val;
        }
        $bcurId = $baseCur->getId();
        $_cur = \Verba\_oh('currency');
        $curOt = $_cur->getID();

        $curId = $mid[1];

        // пересчет курсов измененной валюты относительно других
        $sfId = $this->DB()->escape($curId);
        $link_vault = $_cur->vltURI($_cur);

        $qe = "SELECT DISTINCT cil.*, 
  cl2.ch_ratio AS p_base_ratio,
  cl3.ch_ratio AS ch_base_ratio
FROM " . $link_vault . " cil
  LEFT JOIN " . $link_vault . " AS cl2 ON cl2.p_iid = cil.p_iid && cl2.ch_iid = " . $bcurId . " && cl2.p_ratio = 1
  LEFT JOIN " . $link_vault . " AS cl3 ON cl3.p_iid = cil.ch_iid && cl3.ch_iid = " . $bcurId . " && cl3.p_ratio = 1
WHERE
cil.`p_ot_id` = '" . $curOt . "'
&& cil.`ch_ot_id` = '" . $curOt . "'
&& ((cil.`p_iid` ='" . $sfId . "') 
|| (cil.`ch_iid` = '" . $sfId . "')) 
&& cil.`rule_alias` = ''
  GROUP BY cil.p_iid, cil.ch_iid, cil.p_ot_id, cil.ch_ot_id";
// && cil.`ch_iid` != '".$bcurId."'
        $sqlr = $this->DB()->query($qe);
        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
//        if($other_cur_id == $baseCur->getId()){
//          continue;
//        }
                if ($row['p_iid'] == $curId && $curId == $row['ch_iid']) {
                    $cur1_bratio = $val;
                    $cur2_bratio = $val;
                } elseif ($row['p_iid'] == $curId) {
                    $cur1_bratio = $val;
                    $cur2_bratio = $row['ch_base_ratio'];
                } else {
                    $cur1_bratio = $row['p_base_ratio'];
                    $cur2_bratio = $val;
                }
                list($ot, $pol) = $this->countOtPol($row['ex'], $cur1_bratio, $cur2_bratio);
                $q = "UPDATE " . $link_vault . " SET
`ot` = '" . $this->DB()->escape($ot) . "', `pol` = '" . $this->DB()->escape($pol) . "'
 WHERE `p_ot_id` = " . $curOt . " && `ch_ot_id` = " . $curOt . " 
 && `p_iid` = " . $row['p_iid'] . "
 && `ch_iid` = " . $row['ch_iid'] . "
 && `rule_alias` = ''";

                $sqlrUpd = $this->DB()->query($q);
            }
        }

        // обновление поля 'курс к базовой'
        $rateToBase = $existsData['p_ratio'] / $val;
        $ae = $_cur->initAddEdit('edit');
        $ae->setIid($curId);
        $ae->setGettedData(array(
            'rate' => $rateToBase,
            'rate_val' => $val,
        ));
        $ae->addedit_object();
    }

}
