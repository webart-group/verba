<?php
namespace Verba\Mod\Balop\Cause;

class OrderPayedSellerGravity extends Ordered
{
    protected $otype = 'balop';
    protected $block = 1;
    protected $_itemClassSuffixRequired = 'OrderPayedBuyerEase';

    function getCurrencyId()
    {
        if($this->currencyId === null){
            $this->currencyId = $this->Order->getOCur()->getId();
        }
        return $this->currencyId;
    }

    function calcSum()
    {
        if(!$this->Order || !$this->Order instanceof \Verba\Mod\Order\Model\Order){
            return 0;
        }

        return $this->Order->getSellerSum();
    }

    function getDescription()
    {
        $r = \Verba\Lang::get('balop descriptions orderPayedSellerGravity', array(
            'order_code' => $this->Order->getCode(),
        ));
        return $r;
    }

}
