<?php

namespace Mod\Chatik\Model;

class Message extends \Verba\Model\Item
{
    /**
     * @var $U \Verba\User\Model\User
     */
    protected $U;

    protected $info = array();

    function init()
    {
        $this->info['id'] = $this->getIid();
        $this->info['timestamp'] = strtotime($this->created);
    }

    function U()
    {

        if ($this->U === null) {
            $this->U = new \Verba\User\Model\User($this->owner);
        }

        return $this->U;
    }

    function setU($U)
    {
        if (!$U instanceof \Verba\User\Model\User
            || !$U->getId() != $this->owner) {
            return false;
        }

        $this->U = $U;
    }

    function setInfo($data)
    {
        if (!is_array($data)) {
            return false;
        }
        $this->info = $data;
        return $this->info;
    }

    function addInfo($data)
    {
        if (!is_array($data) || !count($data)) {
            return false;
        }
        $this->info = array_replace_recursive($this->info, $data);
        return $this->info;
    }

    function getInfo()
    {
        return $this->info;
    }
}
