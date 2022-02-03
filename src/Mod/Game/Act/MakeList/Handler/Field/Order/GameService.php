<?php

namespace Mod\Game\Act\MakeList\Handler\Field\Order;

use \Act\MakeList\Handler\Field;

class GameService extends Field
{

    function run()
    {
        if (!is_array($this->list->rowExtended['first_item_extra']['data'])) {
            return '';
        }

        $s = '<span class="order-game-v">' . $this->list->rowExtended['first_item_extra']['data']['gameCatTitle'] . '</span>';
        $s .= '<br><span class="order-service-v">' . $this->list->rowExtended['first_item_extra']['data']['serviceCatTitle'] . '</span>';

        return $s;
    }
}
