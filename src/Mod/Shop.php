<?php

namespace Mod;

class Shop extends \Verba\Mod
{
    public $cppr_table = 'shop_pc_curps'; // currencyPaysysPairsRatio_table

    /**
     * Массив со значениями - trustId => kTimeHold
     *
     * @var array
     */
    protected $_kTimeHold;

    use \Verba\ModInstance;

    public function init(){
        Customer::i();
    }

    function packToCfg()
    {
        return array(
            'url' => $this->gC('url'),
            'currencies' => $this->packCurrenciesToClient(),
        );
    }

    function packCurrenciesToClient()
    {
        $mCurr = \Mod\Currency::getInstance();
        $currs = $mCurr->getCurrencies(false);
        $r = array();
        if (!is_array($currs) || empty($currs)) {
            return $r;
        }
        /**
         * @var $cur \Verba\Model\Currency
         */
        foreach ($currs as $cid => $cur) {
            $r['k' . $cid] = $cur->packToCart();
        }

        return $r;
    }

    /**
     * @param int $currencyId
     * @param \Model\Store $Store
     */
    function getPaymentSelectorItems($currencyId, $Store)
    {

        $paysys_pcs = $Store->getPaysysPcPairsByCurrency($currencyId);
        if (!$paysys_pcs) {
            throw  new \Verba\Exception\Building('Unable to load store cpk');
        }

        $items = array();
        $mPayment = \Verba\_mod('payment');
        foreach ($paysys_pcs as $paysysId => $pc_data) {
            $Ps = $mPayment->getPaysys($paysysId, true);
            if (!$Ps) {
                continue;
            }
            $items[$Ps->id] = array(
                'id' => $Ps->id,
                'title' => $Ps->title,
                'description' => $Ps->description,
                'priority' => $Ps->priority,
                'payment_awaiting' => $Ps->payment_awaiting,
                'minPc' => $pc_data['Pc'],
                'tax_merch_mp' => $Ps->tax_merch_mp
            );
        }

        return $items;
    }

    function recalcCPPR()
    {
        $Currency = Currency::getInstance();
        $Payment = Payment::getInstance();

        $cc = $Currency->getCrossCurrencyProps();

        //$Store = \Mod\Store::getInstance();
        $Shop = \Mod\Shop::getInstance();
        $Nk = (float)$Shop->gC('Nk');

        // Очищение таблицы Pc
        $q = "TRUNCATE TABLE `" . SYS_DATABASE . "`.`" . $Shop->cppr_table . "`";
        $this->DB()->query($q);

        $curs = $Currency->getCurrency();

        /**
         * @var $CurIn \Verba\Model\Currency
         */
        foreach ($curs as $curInId => $CurIn) {
            $pss_inp = $CurIn->getPaysysByLinkRule('input');
            if (!is_array($pss_inp) || !count($pss_inp)) {
                continue;
            }
            // для всех платежек через которые можно оплатить этой валютой
            // просчитваем коэфф для каждой валюты
            foreach ($pss_inp as $psInId => $linkData) {

                $PsIn = $Payment->getPaysys($psInId);

                foreach ($curs as $curOutId => $CurOut) {

                    ## Учитываем обмен между валютами, поле ex в таблице Курсы валют
                    $Ex = isset($cc[$curInId][$curOutId]['ex']) ? $cc[$curInId][$curOutId]['ex'] : 3;

                    ##
                    $balPers = 1 + ($Nk + $Ex) / 100;

                    ## Макс. комиссия за вывод средств, закладывается в цену товара
                    # для покупателя при вводе денег на Биржу
                    $kIn = (float)$CurIn->getPaysysLinkValue('input', $psInId, 'kIn');

                    ## Учитываем комиссию за вывод средств
                    $Pti = $balPers / (1 - $kIn / 100);

                    ## Комиссия платежной системы для биржи (Мерчант для нас)
                    $Mb = $PsIn->tax_merch_m;

                    ## Коэффициент, без учета процента шлюза с покупателя
                    $Pck = $Pti / (1 - $Mb / 100);

                    ## Комиссия платежной системы для покупателя (Мерчант для пок.)
                    $Mp = $PsIn->tax_merch_mp;

                    ## Учитываем комиссию платежной системы с покупателя
                    # (значение для показа цены покупателю)
                    $Pc = $Pck * (1 + $Mp / 100);

                    $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $Shop->cppr_table . "`
      (iCurId, iPaysysId, oCurId, `Ex`, `balPers`, `kIn`, `Pti`, `Pc`, `Pck`, `paysysActive`, `iCurrencyActive`,`oCurrencyActive`)
      VALUES("
                        . $curInId
                        . ", " . $psInId
                        . ", " . $curOutId
                        . ", " . $Ex
                        . ", " . $balPers
                        . ", " . $kIn
                        . ", " . $Pti
                        . ", " . $Pc
                        . ", " . $Pck
                        . ", " . $PsIn->active
                        . ", " . $CurIn->active
                        . ", " . $CurOut->active
                        . ")
      ON DUPLICATE KEY UPDATE
`Ex` = " . $Ex . ", 
`balPers` = " . $balPers . ", 
`kIn` = " . $kIn . ", 
`Pti` = " . $Pti . ", 
`Pc` = " . $Pc . ", 
`Pck` = '" . $Pck . "',
`paysysActive` = '" . $PsIn->active . "',
`iCurrencyActive` = '" . $CurIn->active . "',
`oCurrencyActive` = '" . $CurOut->active . "'";

                    $this->DB()->query($q);
                }
            }

        }

        return true;
    }

    function refreshCpprSystem()
    {
        set_time_limit(600);
        $this->recalcCPPR();

        $mStore = \Mod\Store::getInstance();
        $mStore->refreshStoresCPK();

    }

    function convertFromBase($baseVal, $currencyId)
    {
        if (!is_numeric($baseVal) || $baseVal == 0) {
            return false;
        }
        $baseVal = (float)$baseVal;
        $currency = \Verba\_mod('currency')->getCurrency($currencyId);

        return $baseVal * $currency->p('rate');
    }

    function convertToBase($curVal, $currencyId)
    {
        if (!is_numeric($curVal) || $curVal == 0) {
            return false;
        }
        $curVal = (float)$curVal;
        $currency = \Verba\_mod('currency')->getCurrency($currencyId);

        return $curVal / $currency->rate;
    }

    function convertCur($val, $curInId, $curOutId, $rateIn = false, $rateOut = false)
    {

        if (!is_numeric($val)) {
            return false;
        }

        $val = (float)$val;
        if ($val == 0) {
            return $val;
        }
        if ($curInId == $curOutId) {
            return $val;
        }

        /**
         * @var $mCurrency \Mod\Currency
         */
        $mCurrency = \Verba\_mod('currency');
        if (!$rateIn) {
            $CurIn = $mCurrency->getCurrency($curInId);
            $rateIn = $CurIn->rate;
        } else {
            $rateIn = (float)$rateIn;
        }

        if (!$rateOut) {
            $CurOut = $mCurrency->getCurrency($curOutId);
            $rateOut = $CurOut->rate;
        } else {
            $rateOut = (float)$rateOut;
        }

        if (!$rateIn || !$rateOut) {
            return false;
        }

        return $val / $rateIn * $rateOut;
    }

    function crossrate($curInId, $curOutId)
    {
        /**
         * @var $mCurrency \Mod\Currency
         */
        $mCurrency = \Verba\_mod('currency');

        $CurIn = $mCurrency->getCurrency($curInId);
        $rateIn = $CurIn->rate;

        $CurOut = $mCurrency->getCurrency($curOutId);
        $rateOut = $CurOut->rate;

        if (!$rateIn || !$rateOut) {
            return false;
        }

        return 1 / $rateIn * $rateOut;
    }

    /**
     * @param $timestamp integer
     * @return string
     */
    static function formatDate($timestamp)
    {
        return strftime('%d/%m/%y %H:%M', $timestamp);
    }

    /**
     * @param $sum float
     * @param $Cur \Verba\Model\Currency|integer
     *
     * @return string
     */
    static function formatSum($sum, $Cur)
    {

        if (is_numeric($Cur)) {
            $Cur =  \Mod\Currency::getInstance()->getCurrency($Cur);
        }

        return '<span class="o-cur-sym">' . \Verba\reductionToCurrency($sum) . '<span>' . (!$Cur instanceof \Verba\Model\Currency ? '??' : $Cur->symbol) . '</span></span>';
    }

    /**
     * Возвращает коэф. холда в зависимости от id статуса доверя
     * @param $trustId
     */
    function getKTimeHoldByTrust($trustId)
    {
        if ($this->_kTimeHold === null) {
            $this->_kTimeHold = array();
            $_ut = \Verba\_oh('usertrust');
            $qm = new \Verba\QueryMaker($_ut, false, array('id', 'kTimeHold'));
            $sqlr = $qm->run();
            if ($sqlr && $sqlr->getNumRows() > 0) {
                while ($row = $sqlr->fetchRow()) {
                    $this->_kTimeHold[$row['id']] = (float)$row['kTimeHold'];
                }
            }
        }

        return is_array($this->_kTimeHold) && array_key_exists($trustId, $this->_kTimeHold)
            ? $this->_kTimeHold[$trustId]
            : false;
    }
}
