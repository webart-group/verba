<?php

namespace Verba\Mod\Telegram\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;

class SaveChatId extends Json
{
    function build()
    {
        return _mod('telegram')->saveChatId();
    }
}
