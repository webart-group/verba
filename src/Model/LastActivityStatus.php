<?php

namespace Verba\Model;

trait LastActivityStatus
{
    function getOnlineStatus()
    {
        if (!array_key_exists('__online_status', $this->_prepared)) {
            $this->_prepared['__online_status'] = \Verba\_mod('user')->getOnlineStatusByDatetime($this->data['last_activity']);
        }

        return $this->_prepared['__online_status'];
    }
}
