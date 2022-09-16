<?php

class paysys_status extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'shop/paysys/status/wrap.tpl',
        'payment-button' => 'shop/paysys/status/payment-button.tpl',
        'payment-paysys-title-row' => 'shop/paysys/status/payment-paysys-title-row.tpl',
        'statusmsg' => 'shop/paysys/status/statusmsg.tpl',
        'payment-awaiting' => 'shop/paysys/status/payment-awaiting.tpl',
        'payment-awaiting-hours' => 'shop/paysys/status/payment-awaiting-hours.tpl',
        'payment-button-extend' => '',
    );

    public $tplvars = array(
        'ORDER_PAYMENT_EXTEND' => '',
    );
    /**
     * @var object \Verba\Mod\Order\Model\Order
     *
     */
    public $o;

    public $grid_cfg;

    public $parsePaysysTitleRow = true;

    function init()
    {
        $this->o = $this->request->getParam('order');
        if (is_array($this->grid_cfg)) {
            $this->tpl->assign($this->grid_cfg);
        }
    }

    function build()
    {

        if (!$this->o instanceof \Verba\Mod\Order\Model\Order) {
            return '';
        }

        $topay = $this->o->getToPay();
        $paysys = $this->o->getPaysys();
        $curr = $this->o->getCurrency();

        $this->tpl->assign(array('ORDER_ID' => $this->o->code));

        if (!$this->o->statusMsg) {
            $this->tpl->assign(array('ORDER_PAYMENT_STATUS_DETAILS_ROW' => ''));
        } else {
            $this->tpl->assign(array('ORDER_PAYMENT_STATUS_MSG' => htmlspecialchars($this->o->statusMsg)));
            $this->tpl->parse('ORDER_PAYMENT_STATUS_DETAILS_ROW', 'statusmsg');
        }

        $this->tpl->assign(array(
            // payment button
            'ORDER_PAYMENT_BUTTON_ROW' => $this->parsePaymentButton(),
            // payment time
            'ORDER_PAYMENT_AWAITING_ROW' => $this->parseAwaiting(),
            'ORDER_PAYMENT_STATUS_CODE' => 'status-' . $this->o->status,
            'ORDER_PAYMENT_STATUS' => $this->o->status__value,
            'ORDER_PAYMENT_CURRENCY_TITLE' => $curr->p('title'),
            'ORDER_PAYMENT_CURRENCY_SHORT' => $curr->short,
            'ORDER_PAYMENT_TOPAY' => $topay,
            'ORDER_PAYMENT_PAYSYS_TITLE_ROW' => '',
        ));

        if ($this->parsePaysysTitleRow) {
            $this->tpl->assign(array(
                'ORDER_PAYMENT_PAYSYS_TITLE' => $paysys->title,
            ));
            $this->tpl->parse('ORDER_PAYMENT_PAYSYS_TITLE_ROW', 'payment-paysys-title-row');
        }

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }

    function parseAwaiting()
    {
        $this->tpl = $this->tpl();
        $endTime = strtotime($this->o->endPaymentTime);
        $now = time();
        $d = $endTime - $now;
        if ($d < 1
            || $this->o->status != 20) {
            return '';
        }

        $endDate = date("Y-m-d H:i", $endTime);
        if ($d < 1) {
            $this->tpl->assign(array('ORDER_PAYMENT_AWAITING_HOURS' => ''));
        } else {
            if ($d < 300) {
                $leftTime = \Verba\Lang::get('order paymentAwaiting less5min');
            } elseif ($d < 3600) {
                $minLeft = ceil($d / 60);
                $leftTime = $minLeft . ' ' . \Verba\make_padej_ru($minLeft, \Verba\Lang::get('order paymentAwaiting rootes minutes root'),  \Verba\Lang::get('order paymentAwaiting rootes minutes padej'));
            } else {
                $leftHours = floor($d / 3600);
                $leftTime = $leftHours . ' ' . \Verba\make_padej_ru($leftHours, \Verba\Lang::get('order paymentAwaiting rootes hours root'),  \Verba\Lang::get('order paymentAwaiting rootes hours padej'));
                $secLeft = $d - $leftHours * 3600;
                if ($secLeft > 60) {
                    $minLeft = floor($secLeft / 60);
                    $minLeft = $minLeft . ' ' . \Verba\make_padej_ru($minLeft, \Verba\Lang::get('order paymentAwaiting rootes minutes root'),  \Verba\Lang::get('order paymentAwaiting rootes minutes padej'));
                }
                if (isset($leftTime)) {
                    $leftTime .= ' ' . $minLeft;
                }
            }

            $this->tpl->assign(array('ORDER_PAYMENT_AWAITING_LEFT' => $leftTime));
            $this->tpl->parse('ORDER_PAYMENT_AWAITING_HOURS', 'payment-awaiting-hours');
        }

        $this->tpl->assign(array(
            'ORDER_PAYMENT_AWAITING_DATE' => $endDate,
        ));

        return $this->tpl->parse(false, 'payment-awaiting');
    }

    function parsePaymentButton()
    {
        $pspr =  \Verba\Mod\Payment::i()->getPaysysMod($this->o->paysys->getId());
        if (!$pspr) {
            return 'Unable to initiate Paysys Verba\Mod';
        }
        if ($this->o->status != 20 || !$pspr->gC('haveGataway')) {
            return '';
        }

        $topay = $this->o->getTopay();
        $curr = $this->o->getCurrency();
        $paysys = \Verba\Mod\Payment::i()->getPaysys($this->o->paysys->getId());

        $url = \Verba\Mod\Order::i()->gC('url processpayment');
        $url = new \Url($url);

        $this->tpl->assign(array(
            'ORDER_PAYMENT_URL' => $url->get(true),
            'ORDER_PAYMENT_BUTTON_TITLE' => \Verba\Lang::get('order payButtonTitle', array(
                'topay' => number_format($topay, 2, '.', ' '),
                'unit' => $curr->symbol,
                'paysys_title' => $paysys->title,
            )),
            'ORDER_PAYMENT_EXTEND' => $this->parsePaymentButtonExtend(),
        ));
        return $this->tpl->parse(false, 'payment-button');
    }

    function parsePaymentButtonExtend()
    {
        if (!$this->tpl->isDefined('payment-button-extend')) {
            return '';
        }
        return $this->tpl->parse(false, 'payment-button-extend');
    }
}
