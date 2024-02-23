<?php

namespace Verba\Mod\Menu\Blocks;

use Verba\Block\Json;

class MenuLineJson extends Json
{
    public $rootId = null;

    public $order = ['priority' => 'd'];
    public $lastItem;
    public $lastItemIsCurrent = false;
    public $currentId;

//    function prepare()
//    {
//        $this->fire('beforePrepare');
//        /**
//         * @var $mMenu Menu
//         */
//        $mMenu = \Verba\_mod('menu');
//
//        if ($this->lastItem === null) {
//            $this->lastItem = $mMenu->getActiveNode();
//            if ($this->lastItem['url'] == $this->rq->uf_str) {
//                $this->lastItemIsCurrent = true;
//            }
//        }
//        $this->fire('afterPrepare');
//    }

    function build()
    {
        $this->content = [];

        $_menu = \Verba\_oh('menu');

        $this->currentId = is_array($this->lastItem) && !empty($this->lastItem)
            ? $this->lastItem[$_menu->getPAC()]
            : false;

        $QM = new \Verba\QueryMaker($_menu, false, true);
        $cnd = $QM->addConditionByLinkedOT($_menu, $this->rootId);
        $cnd->setRelation(2);
        $QM->addWhere(1, 'active');
        $QM->addOrder($this->order);

        $sqlr = $QM->run();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $this->content;
        }

        $U = \Verba\User();
        $i = 0;
        while ($row = $sqlr->fetchRow()) {
            if (!$U->chr($row['key_id'])) {
                continue;
            }
            $i++;
            $this->content[] = $this->transformItem($row);
        }

        return $this->content;
    }

    function transformItem($row)
    {
        return [
            'url' => $row['url'],
            'title' => $row['title'],
            'css_class' => $row['css_class'],
            'is_selected' => (int)((bool)$row['id'] == $this->currentId),
        ];
    }
}
