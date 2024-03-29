<?php
namespace Verba\Mod\Game\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class OrderStatus extends Field
{
    function run()
    {
        return '<div class="o-status-v">' . $this->list->row['status__value'] . '</div>'
            . \Verba\Mod\Game::parseToGameTimeHtml($this->list->row['created']);
    }

}
