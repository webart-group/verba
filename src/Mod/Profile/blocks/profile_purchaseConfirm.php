<?php

class profile_purchaseConfirm extends profile_purchase
{

    public $contentType = 'json';

    use profile_orderResponse;

    function init()
    {
        parent::init();
        if (!$this->Order->canBeClosedByBuyer()) {
            throw new \Verba\Exception\Routing('Bad action params');
        }
        $this->profileSuccessMsgLKey = 'orderConfirmedSuccessfull';
    }

    function route()
    {
        return $this;
    }

    function build()
    {
        $_order = \Verba\_oh('order');
        $ae = $_order->initAddEdit('edit');
        $ae->setIid($this->Order->getId());
        $ae->setGettedData(array(
            'confirmedBuyer' => 1,
            'status' => 25,
        ));
        $ae->addExtendedData(array(
            '__buyer_confirm_script_key' => SYS_SCRIPT_KEY,
            'ProfileU' => $this->U,
        ));

        $ae->addedit_object();
        if ($ae->haveErrors()) {
            throw  new \Verba\Exception\Building($ae->log()->getMessagesAsString('error'));
        }

        $this->content = $this->wrapResponse($ae);
        return $this->content;

    }
}
