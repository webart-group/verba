<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Order;

use \Act\MakeList\Handler\Field;

class Summ extends Field
{

    public $templates = array(
        'content' => '/profile/orders/seller_sum.tpl'
    );

    public $sharedTpl = true;

    function run()
    {
        $this->tpl->assign(array(
            'VALUE' => $this->list->row['topay'],
            'CUR_SYMBOL' => $this->list->rowExtended['Cur']->symbol,
        ));

        return $this->tpl->parse(false, 'content');
    }
}
