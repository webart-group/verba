<?php

class profile_sellCancelCashback extends profile_sell
{

    public $contentType = 'json';

    use profile_orderResponse;

    function init()
    {
        parent::init();
        if (!$this->Order->canBeCanceledByStore()) {
            throw new \Verba\Exception\Routing('Bad action params');
        }
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
            'status' => 22,
        ));
        $ae->addExtendedData(array(
            '__seller_cancel_cashback_script_key' => SYS_SCRIPT_KEY,
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
