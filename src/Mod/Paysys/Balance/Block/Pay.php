<?php
namespace Mod\Paysys\Balance\Block;

class Pay extends \Verba\Block\Html
{

    /**
     * @var $U \Verba\User\Model\User
     */
    public $U;

    /**
     * @var $Order \Mod\Order\Model\Order
     */
    public $Order;

    function route()
    {
        $this->U = User();
        $this->Order = \Verba\_mod('order')->getOrderByCode($this->rq->getParam('orderId'));
        if (!$this->Order || !$this->Order->getId()) {
            throw new \Exception\Routing('Bad request');
        }

        if (!$this->U->getAuthorized()
            || $this->Order->owner != $this->U->getId()) {
            throw new \Exception\Routing('Unauthorized request');
        }

        if ($this->Order->payed || !$this->Order->active) {
            throw new \Exception\Routing('Wrong action');
        }

        return $this;

    }

    function build()
    {
        $orderCurrency = $this->Order->getCurrency();
        $Acc = $this->U->Accounts()->getAccountByCur($orderCurrency);
        if (!$Acc || !$Acc->active) {
            throw  new \Verba\Exception\Building('Bad account');
        }

        if (!$Acc->isSumApproved($this->Order->getByuerSum() * -1)) {
            throw  new \Verba\Exception\Building('Sum not approved');
        }
        /**
         * @var $mOrder \Mod\Order
         */
        $mOrder = \Verba\_mod('order');
        $valid_payment_statuses = $mOrder->gC('paymentStatusAliases');
        $_order = \Verba\_oh('order');
        $ae = $_order->initAddEdit('edit');
        $ae->setIID($this->Order->getId());

        $data = array();

        $data['status'] = $valid_payment_statuses['success'];

        $data['payed'] = 1;

        $ae->addExtendedData(array(
            '__OrderPaymentCause' => $Acc
        ));


        $ae->setGettedObjectData($data);

        $ae->addedit_object();

        /**
         * @var $mProfile Profile
         */
        $mProfile = \Verba\_mod('profile');
        $url = new \Url($mProfile->getPurchaseActionUrl($this->Order));
        $this->addHeader('Location', $url->get());

    }

}
