<?php

namespace Mod;

class Profile extends \Verba\Mod
{
    use \Verba\ModInstance;

    static function getPrivateUrl()
    {
        return '/profile/';
    }

    function getPublicUrl($userId)
    {
        return '/p/' . $userId;
    }

    function getSellOrderUrl($row, $data = array())
    {

        return '/profile/sells/' . $row['code'];

    }

    //  Url's
    protected function getOrderActionUrlBase($side)
    {
        if ($side != 'purchases' && $side != 'sells') {
            return false;
        }
        return '/profile/' . $side;
    }

    protected function getOrderActionUrl($side, $row = false, $orderAction = false)
    {

        $urlBase = SYS_REQUEST_PROTO . '://' . SYS_THIS_HOST . $this->getOrderActionUrlBase($side);
        if (!$urlBase) {
            return $urlBase;
        }

        if (is_array($row)) {
            $code = $row['code'];
        } elseif (is_object($row) && $row instanceof \Mod\Order\Model\Order) {
            $code = $row->getCode();
        }

        if (!isset($code) || !\Mod\Order::getInstance()->isOrderCode($code)) {
            return $urlBase;
        }

        $r = $urlBase . '/' . $code;

        if (is_string($orderAction) && !empty($orderAction)) {
            $r .= '/' . $orderAction;
        }

        return $r;
    }

    function getPurchaseActionUrl($row = false, $orderAction = '')
    {
        return $this->getOrderActionUrl('purchases', $row, $orderAction);
    }

    function getSellActionUrl($row = false, $orderAction = '')
    {
        return $this->getOrderActionUrl('sells', $row, $orderAction);
    }
}
