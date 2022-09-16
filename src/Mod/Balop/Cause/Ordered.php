<?php
namespace Verba\Mod\Balop\Cause;

use Verba\Mod\Balop\Cause;

class Ordered extends Cause{

    /**
     * @var \Verba\Mod\Order\Model\Order
     */

    protected $Order;

    function init(){
        $orderOtId = \Verba\_oh('order')->getID();
        if(!$this->primitiveOt){
            $this->primitiveOt = $orderOtId;
        }

        if(!empty($this->primitiveId) && $this->primitiveOt == $orderOtId){
            $this->Order = \Verba\Mod\Order::i()->getOrder($this->primitiveId);
        }

    }

}