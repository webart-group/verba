<?php

namespace Verba\Mod\Review\Block;

use Verba\Block\Json;
use Verba\Lang;
use function Verba\_oh;

class PublicList extends Json
{

    function build()
    {
        $_rw = \Verba\_oh('review');
        $qm = new \Verba\QueryMaker($_rw, false, true);
        $qm->addWhere(1, 'active');
        $qm->addOrder(array($_rw->getPAC() => 'd'));
        $qm->addConditionByLinkedOT(_oh($this->request->ot_id), $this->request->iid);
        $q = $qm->getQuery();

        $sqlr = $qm->run();
        $this->content = [];

        if (!$sqlr || !$sqlr->getNumRows()) {
            return $this->content;
        }

        while ($row = $sqlr->fetchRow()) {
            $time = strtotime($row['created']);
            $mname = Lang::get('date m ' . date('n', $time));

            $this->content[] = [
                'created_at' => date('d ' . $mname . ' Y', $time),
                'author' => htmlspecialchars($row['name']),
                'text' => htmlspecialchars($row['review']),
            ];
        }

        return $this->content;
    }
}
