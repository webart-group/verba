<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

use Verba\Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

class Buyer extends Nom
{

    protected $profileLinkCode = 'seller';

    function prepare()
    {
        parent::prepare();
        $this->profileUrl = \Verba\Mod\Store::getInstance()->getPublicUrl($this->list->row['storeId'], 'info');
        $this->profileText = $this->list->row['storeId__value'];
    }

}
