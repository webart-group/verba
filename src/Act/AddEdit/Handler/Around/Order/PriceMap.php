<?php

namespace Verba\Act\AddEdit\Handler\Around\Order;

use \Verba\Act\AddEdit\Handler\Around;

class PriceMap extends Around
{
    function run()
    {
        if($this->action != 'new'){
            return null;
        }
        $iCurId = $this->ah->getTempValue('currencyId');
        $iPaysysId = $this->ah->getTempValue('paysysId');
        $ocd = $this->ah->getExtendedData('orderCreateData');
        if(!$ocd || !$ocd instanceof \Verba\Mod\Order\CreateData || !$ocd->Store
            || !$iCurId
            || !$iPaysysId){
            $this->log()->error('Unable to locate required data');
        }

        $pc_data = $ocd->Store->getPcOutData($iCurId, $iPaysysId);
        /**
         * @var $Cart \Mod\Cart\CartInstance
         */
        $Cart = $this->ah->getExtendedData('cart');
        $items_price = $Cart->getItemsPrice();
        $iCur = $Cart->getCurrency();
        $r = array(
            'Ex' => $pc_data['Ex'],
            'balPers' => $iCur->round($pc_data['balPers']),
            'Pc' => $pc_data['Pc'],
            'Pck' => $pc_data['Pck'],
            'ps_tax' => $iCur->round(($pc_data['Pc'] - $pc_data['balPers']) * $items_price),
            'oCurId' => $pc_data['oCurId'],
            'crossrate' => \Mod\Shop::getInstance()->crossrate($iCur->getId(), $pc_data['oCurId']),
        );
        $r = json_encode($r);
        return $r;
    }
}
