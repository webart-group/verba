<?php

namespace Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

use Mod\Game\Act\MakeList\Handler\Field\Order\Nom;

class Seller extends Nom
{
    protected $profileLinkCode = 'buyer';

    function prepare()
    {
        parent::prepare();
        $this->profileUrl = \Verba\_mod('profile')->getPublicUrl($this->list->row['owner']);
        $this->profileText = $this->list->row['owner__value'];
    }
}
