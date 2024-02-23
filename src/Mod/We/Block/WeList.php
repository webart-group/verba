<?php

namespace Verba\Mod\We\Block;

use Verba\Block\Json;
use Verba\Mod\We\Transformers\Person;
use Verba\QueryMaker;
use function Verba\_oh;

class WeList extends Json
{
    function build()
    {
        $this->content = [];

        $_we = _oh('we');

        $qm = new QueryMaker($_we, false, true);
        $qm->addOrder(['priority' => 'd']);
        $qm->addWhere(1, 'active');
        $sqlr = $qm->run();

        if(!$sqlr || !$sqlr->getNumRows()){
            return $this->content;
        }

        $transformer = new Person();
        while($item = $sqlr->fetchRow()) {
            $this->content[] = $transformer->transform($item);
        }

        return $this->content;
    }
}
