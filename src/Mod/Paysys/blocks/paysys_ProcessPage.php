<?php

class ExceptionPaymentInterrupt extends Exception
{
    public $report_type = 'error';
}

class ExceptionPaymentInterruptInfo extends Exception
{
    public $report_type = 'info';
}

class paysys_ProcessPage extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'shop/paysys/process/content.tpl',
        'report-msg' => 'shop/paysys/process/report-msg.tpl',
    );

    /**
     * @var $Order \Verba\Mod\Order\Model\Order
     */
    public $Order;

    /**
     * @var $Psmod PaySystemBase
     */
    public $Psmod;

    function route()
    {
        try {
            $orderId = $this->request->iid;

            $this->Order = \Verba\_mod('order')->getOrderByCode($orderId);

            if (!$this->Order instanceof \Verba\Mod\Order\Model\Order) {
                throw new \Verba\Exception\Routing('Unknown order');
            }
            $this->Psmod = \Verba\_mod('payment')->getPaysysMod($this->Order->paysysId, true);
            if (!is_object($this->Psmod)) {
                $this->log()->error('Unable to load PaysysModule for order_id: ' . var_export($orderId, true) . ', paysys_id: ' . var_export($this->Order->paysysId, true) . '.');
                throw new \Verba\Exception\Routing('Unable to load PaysysModule');
            }

            $payTransactionHandler = $this->Psmod->getPayTransactionHandler();
            if (!class_exists($payTransactionHandler)) {
                throw new ExceptionPaymentInterrupt('Unknown handler');
            }

            $bForm = new \paysys_requestForm($this->request, array(
                'Order' => $this->Order,
                'Psmod' => $this->Psmod,
                'PaySend' => new $payTransactionHandler($this->Order->getId()),
            ));

            $this->addItems(array(
                'RQ_FORM' => $bForm
            ));
        } catch (Exception $e) {
            $this->handleProcessException($e);
        }

        $handler = new \App\Layout\Local($this->request);
        $handler->addItems(array(
            'CONTENT' => $this
        ));

        return $handler->route();

    }

    function buildItems()
    {
        try {

            parent::buildItems();

        } catch (Exception $e) {

            $this->handleProcessException($e);

        }
    }

    function handleProcessException($e)
    {
        $this->log()->error($e);

        $this->tpl->assign(array(
            'REPORT_TYPE' => 'error',
            'REPORT_TITLE' => \Verba\Lang::get('paysys payment report-msg title', array(
                'orderCode' => $this->Order->code
            )),
            'REPORT_MESSAGE' => \Verba\Lang::get('paysys payment process send_form_creating_error'),
        ));

        $this->content = $this->tpl->parse(false, 'report-msg');
    }

    function build()
    {
        if (is_string($this->content)) {
            return $this->content;
        }
        return parent::build();
    }
}
