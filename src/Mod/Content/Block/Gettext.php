<?php

namespace Verba\Mod\Content\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;

class Gettext extends Json
{
    function build()
    {
        $this->content = [];

        $requested_code = $this->rq->node;
        if(!$requested_code){
            return $this->content;
        }

        $_content = _oh('content');

        $qm = new QueryMaker($_content, false, true);
        $qm->addOrder(['priority' => 'd']);
        $qm->addWhere(1, 'active');
        $qm->addWhere($requested_code, 'id_code');
        $sqlr = $qm->run();

        if (!$sqlr || !$sqlr->getNumRows()) {
            return $this->content;
        }

        $this->content = $sqlr->fetchRow();


        return $this->content;
    }
}