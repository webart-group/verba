<?php

class profile_orderBtn extends page_eInteractive
{

    /**
     * @var \Verba\Mod\Order\Model\Order
     */
    public $Order;

    public $code;
    protected $_orderSide = 'order';

    function init()
    {

        if (!is_object($this->Order) || !$this->Order instanceof \Verba\Mod\Order\Model\Order) {
            throw new Exception();
        }

        $this->eid = $this->_orderSide . '_' . $this->Order->getCode() . '_btn_' . $this->code;

        if (!is_string($this->group)) {
            $this->group = $this->_orderSide . '-' . $this->Order->getCode() . '-btns';
        }

    }
}
