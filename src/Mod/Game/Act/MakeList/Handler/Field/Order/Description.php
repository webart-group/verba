<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Order;

use \Act\MakeList\Handler\Field;

class Description extends Field
{
    function run()
    {
        if (!empty($this->list->row['description'])) {
            return htmlspecialchars($this->list->row['description']);
        }

        if (!is_array($this->list->rowExtended['first_item_extra'])) {
            return '';
        }

        return $this->list->rowExtended['first_item_extra']['description'];
    }
}
