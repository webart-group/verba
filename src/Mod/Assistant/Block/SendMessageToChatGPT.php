<?php

namespace Verba\Mod\Assistant\Block;

use Verba\Block\Json;
use Verba\Mod\Assistant;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;

class SendMessageToChatGPT extends Json
{
    function build()
    {
        parent::build();
        /**
         * @var Assistant $mAssistant
         */

        $mAssistant = _mod('assistant');

        $this->content = $mAssistant->sendMessageToChatGPT($this->rq);

        return $this->content;
    }
}
