<?php

namespace Mod;

class Offer extends \Verba\Mod
{
    use \Verba\ModInstance;
    static function getOfferUrl($row, $data = array())
    {
        return \Mod\Seo::idToSeoStr($row, $data, '/offer/');
    }

}
