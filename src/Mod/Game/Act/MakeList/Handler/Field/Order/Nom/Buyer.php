<?php

namespace Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

use Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

class Buyer extends Nom
{

    protected $profileLinkCode = 'seller';

    function prepare()
    {
        parent::prepare();
        $this->profileUrl = \Mod\Store::getInstance()->getPublicUrl($this->list->row['storeId'], 'info');
        $this->profileText = $this->list->row['storeId__value'];
    }

}
