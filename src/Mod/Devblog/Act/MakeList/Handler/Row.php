<?php

namespace Verba\Mod\Devblog\Act\MakeList\Handler;

use Verba\Act\MakeList\Handler\Row as RowDefault;

class Row extends RowDefault
{

    function run()
    {
        $tpl = $this->list->tpl();

        $tpl->assign(array(
            'ITEM_CREATION_DATE' => date('Y-d-m H:i:s', strtotime($this->list->row['created'])),
            'ITEM_TITLE' => $this->list->row['title'],
            'ITEM_TEXT' => $this->list->row['annotation'],
            'ITEM_URL_HREF' => '/devblog/'.$this->list->row['id'],
        ));

        return true;
    }
}
