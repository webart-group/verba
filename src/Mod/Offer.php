<?php

namespace Verba\Mod;

class Offer extends \Verba\Mod
{
    use \Verba\ModInstance;
    static function getOfferUrl($row, $data = array())
    {
        return \Verba\Mod\Seo::idToSeoStr($row, $data, '/offer/');
    }

}
