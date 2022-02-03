<?php

namespace Mod\WS;

interface ChannelInterface {

    /**
     * @param $U \U|integer
     * @return bool
     */
    function userHasAccess($U);

    /**
     * @param $parts Channel\Parts
     * @return Channel\Parts
     */
    function setParts($parts);
}
