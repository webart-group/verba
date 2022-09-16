<?php
namespace Verba\Mod\Game\Act\Look\Handler;

use Act\Look\Handler;

class StartAtWithDeal extends Handler
{
    function run()
    {
        $ts = strtotime($this->value);

        if ($this->value == '0000-00-00 00:00:00' || !$ts || date('Y', $ts) < 2000) {
            return \Verba\Lang::get('game view startAsWithDeal');
        }

        return \Verba\Mod\Shop::formatDate($ts);
    }
}
