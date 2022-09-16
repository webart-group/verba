<?php

class order_emailStatusUpdated extends order_email
{

    protected $tpl_base = '/shop/order/email/statusUpdate';

    public $subjLangKey = 'order statusUpdate subject';

    /**
     * 'not_paid' => 20,
     * 'success' => 21,
     * 'error' => 24,
     * 'wait' => 25,
     * 'not_valid' => 26,
     * 'canceled' => 23,
     * 'overdue' => 27,
     */
    function prepare()
    {

        parent::prepare();

        $status_exists = $this->ae->getExistsValue('status');

        $status_new = $this->ae->getActualValue('status');

        $status_values = $this->ae->oh()->A('status')->getValues();

        if (!$status_values)
        {
            $status_values = [];
        }

        $statusMsg = $this->ae->getTempValue('statusMsg');

        $this->tpl->clear_vars('ORDER_STATUS_MSG_ROW');

        if (is_string($statusMsg) && \mb_strlen($statusMsg)) {
            $this->tpl->assign(array('ORDER_STATUS_MSG' => htmlspecialchars($statusMsg)));
            $this->tpl->parse('ORDER_STATUS_MSG_ROW', 'status_msg');
        } else {
            $this->tpl->assign('ORDER_STATUS_MSG_ROW', '');
        }

        $this->tpl->assign([
            'ORDER_STATUS' => (isset($status_values[$status_new])
                ? htmlspecialchars($status_values[$status_new])
                : '??'),
            'ORDER_STATUS_PREV' => htmlspecialchars($status_values[$status_exists]),
            ''
        ]);

        $this->tpl->parseLang('order statusUpdate body', 'ORDER_MESSAGE_BODY', $this->Order->locale);
    }
}
