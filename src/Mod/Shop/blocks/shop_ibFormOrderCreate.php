<?php

class shop_ibFormOrderCreate extends \Verba\Block\Json
{
    /**
     * @var \Verba\Mod\User\Model\User
     */
    protected $U;
    /**
     * @var \Verba\Mod\Cart\CartInstance
     */
    protected $Cart;

    /**
     * @var \Verba\Mod\Customer\Profile
     */
    protected $cst;

    public $currencyId;
    public $paysysId;

    function prepare()
    {
        $this->U = getUser();

        if (!$this->U->getAuthorized()) {
            throw  new \Verba\Exception\Building('Unknown user');
        }

        $this->Cart = \Verba\_mod('cart')->getCart();
        if (!$this->Cart->getItemsCount()) {
            throw  new \Verba\Exception\Building('Cart is empty');
        }

        $this->cst = $this->Cart->getProfile();
        if (!$this->cst || !$this->cst->getEmail() || !$this->cst->getNumericId()) {
            throw  new \Verba\Exception\Building('Invalid customer profile');
        }
        if ($this->U->email != $this->cst->getEmail()) {
            throw  new \Verba\Exception\Building('User and customer profile miss');
        }
        $cartCurrency = $this->Cart->getCurrency();
        $currency = \Verba\_mod('currency')->getCurrency($cartCurrency->getId());

        if (!$currency) {
            throw  new \Verba\Exception\Building('Bad currency');
        }

        $this->currencyId = (int)$currency->getId();
        $this->paysysId = (int)$this->rq->getParam('paysysId');

        if (!$this->paysysId || !$currency->isPaysysLinkExists($this->paysysId, 'input')) {
            throw  new \Verba\Exception\Building('Bad currency or paysys');
        }
    }

    function build()
    {
        $this->content = '';

        $mOrder = \Verba\_mod('order');

        $cfg = array(
            'data' => array(
                'email' => $this->cst->getEmail(),
                'paysysId' => $this->paysysId,
                'currencyId' => $this->currencyId,
            ),
        );

        $orderCreateData = new \Verba\Mod\Order\CreateData($cfg);
        /**
         * @var $Order \Verba\Mod\Order\Model\Order
         */
        list($ae, $Order) = $mOrder->createOrder($orderCreateData);
        /**
         * @var $mProfile \Verba\Mod\Profile
         */
        $mProfile = \Verba\_mod('profile');
        $url = new \Url($mProfile->getPurchaseActionUrl($Order));

        $this->content = $url->get(true);
        return $this->content;
    }
}
