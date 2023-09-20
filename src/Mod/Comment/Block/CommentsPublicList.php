<?php

namespace Verba\Mod\Comment\Block;

use Verba\Block\Json;
use Verba\Lang;
use Verba\QueryMaker;
use function Verba\_oh;

class CommentsPublicList extends Json
{
    function build()
    {
        $_comm = _oh('comment');
        $qm = new QueryMaker($_comm, false, true);
        $qm->addWhere(1, 'active');
        $qm->addOrder(array($_comm->getPAC() => 'd'));
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
                'comment' => htmlspecialchars($row['comment']),
            ];
        }

        return $this->content;
    }
}
