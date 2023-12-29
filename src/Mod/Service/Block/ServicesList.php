<?php

namespace Verba\Mod\Service\Block;

use Verba\Block\Json;
use Verba\Lang;
use Verba\Mod\Service\Transformers\Service;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class ServicesList extends Json
{
    public string $transformerClass = Service::class;

    function build()
    {
        $this->content = [];

        $_menu = _oh('menu');

        $qm = new QueryMaker($_menu, false, true);
        $qm->addWhere('/services','url');
        $qm->addWhere('/#services','url');
        $qm->addLimit(1);

        $menuSqlr = $qm->run();

        $menu = $menuSqlr->fetchRow();

        $_content = _oh('content');

        $qm = new QueryMaker($_content, false, true);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addWhere(1, 'active');
        $cnd = $qm->addConditionByLinkedOT($_menu->getOtId(), $menu['id']);
        $cnd->setRelation(2);
        $q = $qm->getQuery();
        $sqlr = $qm->run();

        if(!$sqlr || !$sqlr->getNumRows()){
            return $this->content;
        }
        $transformerClass = $this->transformerClass;
        $transformer = new $transformerClass();
        while($item = $sqlr->fetchRow()) {
            $this->content[] = $transformer->transform($item);
        }

        return $this->content;
    }
}
