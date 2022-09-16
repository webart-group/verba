<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Order\Summ;

use \Verba\Mod\Game\Act\MakeList\Handler\Field\Order\Summ;

class Buyer extends Summ
{
    function run()
    {
        $r = parent::run();
        $r .= '<div class="o-paysys-v">' . $this->list->row['paysysId__value'] . '</div>';

        return $r;
    }
}
