<?php

namespace Verba\Mod\Index\Block;

use Verba\Block\Json;
use Verba\Lang;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class ServicesPromo extends Json
{
    function build()
    {
        $this->content = [];

        $_menu = _oh('menu');

        $qm = new QueryMaker($_menu, false, true);
        $qm->addWhere('/services','url');
        $qm->addLimit(1);

        $menuSqlr = $qm->run();

        $menu = $menuSqlr->fetchRow();

        $_content = _oh('content');

        $qm = new QueryMaker($_content, false, true);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addWhere(1, 'active');
        $cnd = $qm->addConditionByLinkedOT($_menu->getOtId(), $menu['id']);
        $cnd->setRelation(2);
        $sqlr = $qm->run();

        if(!$sqlr || !$sqlr->getNumRows()){
            return $this->content;
        }

        while($item = $sqlr->fetchRow()) {

            $row = [
                'title' => $item['title'] ?? null,
                'text_preview' => $item['text_preview'] ?? null,
            ];

            $this->content[] = $row;
        }

        return $this->content;
    }
}
