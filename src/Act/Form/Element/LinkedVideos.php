<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;
use Verba\Act\MakeList;
use function Verba\_oh;


class LinkedVideos extends Element
{
    public $templates = [];

    function makeE()
    {
        $this->fire('makeE');

        $cfg =[
            'ot_id' => _oh('rvideo')->getID(),
            'cfg' => 'acp/list acp/ots/rvideo',
            'listId' => $this->ah()->getId().'_rvideos'
        ];

        $pot = $this->ah()->getOtId();
        $piid = $this->ah()->getIid();

        $list = new MakeList($cfg);
        $list->addParents($pot, $piid);
        $list->generateList();
        $this->setE($list->generateList());

        $this->fire('makeEFinalize');
    }

}