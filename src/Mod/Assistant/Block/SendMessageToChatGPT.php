<?php

namespace Verba\Mod\Assistant\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;

class SendMessageToChatGPT extends Json
{
    function build()
    {
        return _mod('assistant')->sendMessageToChatGPT($this->rq);
    }
}
