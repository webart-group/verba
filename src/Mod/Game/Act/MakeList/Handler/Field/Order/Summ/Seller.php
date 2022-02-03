<?php

namespace Mod\Game\Act\MakeList\Handler\Field\Order\Summ;

use \Mod\Game\Act\MakeList\Handler\Field\Order\Summ;

class Seller extends Summ
{
    function run()
    {
        $this->tpl->assign(array(
            'VALUE' => $this->list->rowExtended['Order']->getSellerSum(),
            'CUR_SYMBOL' => $this->list->rowExtended['Cur']->symbol,
        ));
        return $this->tpl->parse(false, 'content');
    }
}
