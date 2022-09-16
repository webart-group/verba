<?php

class order_Status extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'shop/order/status/body.tpl',
        'statusMsgRow' => 'shop/order/status/statusMsgRow.tpl',
//    'store_link' => 'shop/order/status/store-link.tpl',
//    'store_link_bad' => 'shop/order/status/store-link-bad.tpl',
    );

    public $css = array(
        array('order-status')
    );

    public $tplvars = array(
        'ORDER_ITEMS' => '',
        'ORDER_SUMMARY' => '',
    );

    /**
     * @var \Verba\Mod\Order\Model\Order
     */
    public $Order;
    /**
     * @var string Payment status details classname
     */
    protected $psViewClass;

    public $grid_cfg = array(
        'ORDER_GRID_CS1_C0_WIDTH' => 'col-12',

        'ORDER_GRID_CS2_C0_WIDTH' => 'col-4',
        'ORDER_GRID_CS2_C1_WIDTH' => 'col-8',

        'ORDER_GRID_CS3_C0_WIDTH' => 'col-3',
        'ORDER_GRID_CS3_C1_WIDTH' => 'col-2',
        'ORDER_GRID_CS3_C2_WIDTH' => 'col-7',
    );
    public $pssCfg = array(
        'grid_cfg' => null,
    );
    public $parseSummary = true;

    function init()
    {

        parent::init();

        if (!is_object($this->Order) || !$this->Order instanceof \Verba\Mod\Order\Model\Order || !$this->Order->getId() || !$this->Order->active) {
            throw new \Verba\Exception\Routing();
        }

        if (is_array($this->grid_cfg)) {
            $this->tpl->assign($this->grid_cfg);
        }

    }

    function getCssClassByOrderStatus()
    {
        switch ($this->Order->status) {
            case 20:
                return 'payment-awaiting';
            case 21:
                return 'paid';
            case 23:
                return 'canceled-closed';
            case 24:
                return 'error-payment';
            case 25:
                return 'complete-closed';
            case 26:
                return 'canceled';
            default:
                return 'unknown';
        }
    }

    function parsePaysysInfo()
    {
        /**
         * @var $paysys PaysysItem
         */
        $paysys = $this->Order->getPaysys();

        $psrq = clone $this->request;
        $psrq->addParam(array('order' => $this->Order));
        $pssCfg = $this->fillPssCfg();

        $psViewClass = 'paysys_status' . ucfirst($paysys->getCode());
        if (!class_exists($psViewClass, true)) {
            $psViewClass = 'paysys_status';
        }
        /**
         * @var $psBlock paysys_status
         */
        $psBlock = new $psViewClass($psrq, $pssCfg);
        $psBlock->prepare();

        $this->tpl->assign(array(
            'ORDER_PAYMENT_STATUS' => $psBlock->build(),
            'ORDER_PAYMENT_STATUS_SIGN' => ' ' . $this->getCssClassByOrderStatus(),
        ));
        $this->mergeHtmlIncludes($psBlock);
    }

    function build()
    {

        $this->parsePaysysInfo();

        if (!$this->Order->statusMsg) {
            $this->tpl->assign(array(
                'ORDER_PAYMENT_STATUS_MESSAGE_SIGN' => ' no-status',
                'ORDER_PAYMENT_STATUS_MESSAGE' => '',
            ));
        } else {
            $this->tpl->assign(array(
                'ORDER_PAYMENT_STATUS_MESSAGE_SIGN' => '',
                'ORDER_PAYMENT_STATUS_MESSAGE' => htmlspecialchars($this->Order->statusMsg),
            ));
        }

        $bItems = new order_statusItems($this,
            array(
                'Order' => $this->Order,
                'parsePromotions' => true,
                'grid_cfg' => $this->grid_cfg,
            )
        );
        $bItems->prepare();
        $this->tpl->assign(array('ORDER_ITEMS' => $bItems->build()));

        if ($this->parseSummary) {
            $bSummary = new order_statusSummary($this,
                array(
                    'Order' => $this->Order,
                    'grid_cfg' => $this->grid_cfg,
                )
            );
            $bSummary->prepare();
            $this->tpl->assign(array('ORDER_SUMMARY' => $bSummary->build()));
        } else {
            $this->tpl->assign(array(
                'ORDER_SUMMARY' => ''
            ));
        }

        $mStore = \Verba\Mod\Store::getInstance();

        if (!$this->Order->storeId) {
            $this->tpl->assign('ORDER_STORE_LINK', \Verba\Lang::get('order fields store_no_title_no_id'));
        } else {
//      $storeTitle = htmlspecialchars(
//        (is_string($this->Order->storeId__value)
//        && strlen($this->Order->storeId__value)
//        ? $this->Order->storeId__value
//        : \Verba\Lang::get('order fields store_no_title_valid_id', array('store_id' => $this->Order->storeId))
//        )
//      );
//      $this->tpl->assign(array(
//        'ORDER_STORE_ID' => $this->Order->storeId,
//        'ORDER_STORE_TITLE' => $storeTitle,
//      ));
//      $this->tpl->parse('ORDER_STORE_LINK', 'store_link');

            $this->tpl->assign(array(
                'ORDER_STORE_LINK' => $mStore->getInfoLinkWithPic($this->Order->storeId)
            ));

        }

        /**
         * @var $mCustomer Customer
         */
        $mCustomer = \Verba\_mod('customer');

        $this->tpl->assign(array(
            'ORDER_ID' => $this->Order->getCode(),
            'ORDER_CREATED' => $this->Order->getFormatedCreationDate(),
            'ORDER_NUMBER' => $this->Order->code,
            'ORDER_NAME' => htmlspecialchars($this->Order->name),
            'ORDER_SURNAME' => htmlspecialchars($this->Order->surname),
            'ORDER_PHONE' => $this->Order->phone,
            'ORDER_CUSTOMER_INFO' => $mCustomer->getInfoLink($this->Order->getCustomer()),
            'ORDER_STATUS' => $this->Order->status__value,
            'ORDER_COUNTRY' => htmlspecialchars($this->Order->country__value),
            'ORDER_ADDRESS' => htmlspecialchars($this->Order->address),
            'ORDER_COMMENT' => htmlspecialchars($this->Order->comment),
            'ORDER_CITY' => htmlspecialchars($this->Order->city),
//      'ORDER_STORE_ID' => $this->Order->storeId,
//      'ORDER_STORE_TITLE' => htmlspecialchars($storeTitle),
        ));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }

    function fillPssCfg()
    {
        return array(
            'grid_cfg' => $this->grid_cfg,
        );
    }

}
