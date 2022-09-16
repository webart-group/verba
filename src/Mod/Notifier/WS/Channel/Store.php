<?php
namespace Verba\Mod\Notifier\WS\Channel;
use \Verba\Mod\User\Model\User;
class Store extends \Verba\Mod\WS\Channel
{

    function userHasAccess($U = null)
    {
        if ($U === null) {
            $U = \Verba\User();
        }
        if (!$U instanceof U
            && is_numeric($U)) {
            $U = new U($U);
        }

        return $U instanceof U && $U->getId() && $this->parts->storeId
            && $U->haveStore()
            && $this->parts->storeId == $U->getStoreId();
    }

}

;
