<?php

use Verba\Mod\Payment\Transaction\Send;

use\Mod\Payment;

class paysys_Notify extends \Verba\Block\Html
{

    /**
     * @var Send
     */
    protected $PayNotifyHandler;

    function route()
    {
        $orderId = $this->request->iid;
        $pscode = $this->request->node;

        /**
         * @var \Verba\Mod\Payment\Paysys $psmod
         */
        $psmod = Payment::i()->getPaysysMod($pscode, true);

        if (!$orderId && is_object($psmod)) {
            $orderId = $psmod->extractOrderIdFromEnv();
            $this->request->iid = $orderId;
        }

        $this->log()->d("Paysys (" . var_export($pscode, true) . "), \nextracted OrderId: [" . $orderId . "]\n Request:\n" . var_export($_REQUEST, true));

        if (!is_object($psmod)) {
            $this->log()->error('Unable to load PaysysModule for ps code ' . var_export($pscode, true) . ', order_id: ' . var_export($orderId, true));
            throw new \Verba\Exception\Routing('Unable to load PaysysModule');
        }

        $className = $psmod::getNotifyHandler();
        if (!class_exists($className)) {
            $className = '\\Mod\\' . $psmod->getCode() . '\\Notify';
            if (!class_exists($className)) {
                throw new \Verba\Exception\Routing('Unknown notify handler');
            }
        }

        $PayNotifyHandler = new $className($this->request);
        $response = new \Verba\Response\Raw($this->request);

        if ($PayNotifyHandler instanceof BlockInterface) {
            $response->addItems($PayNotifyHandler);
        } else {
            $this->PayNotifyHandler = $PayNotifyHandler;
            $response->addItems($this);
        }

        return $response;
    }

    function prepare()
    {

    }

    function build()
    {
        if (is_object($this->PayNotifyHandler)) {
            $this->content = $this->PayNotifyHandler->handleRequest();
        }

        return $this->content;
    }
}
