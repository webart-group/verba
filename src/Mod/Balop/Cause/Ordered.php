<?php
namespace Mod\Balop\Cause;

use Mod\Balop\Cause;

class Ordered extends Cause{

    /**
     * @var \Mod\Order\Model\Order
     */

    protected $Order;

    function init(){
        $orderOtId = \Verba\_oh('order')->getID();
        if(!$this->primitiveOt){
            $this->primitiveOt = $orderOtId;
        }

        if(!empty($this->primitiveId) && $this->primitiveOt == $orderOtId){
            $this->Order = \Mod\Order::i()->getOrder($this->primitiveId);
        }

    }

}