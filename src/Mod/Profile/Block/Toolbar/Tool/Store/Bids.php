<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool\Store;

class Bids extends \Verba\Mod\Profile\Block\Toolbar\Tool\Store{

    public $url = '/profile/offers';

    public $badge = array(
        'color' => 'green',
    );

    public $icon = array(
        'src' => 'my-lots',
        'w' => 20,
        'h' => 26,
    );
    public $code = 'bids';
    public $cssClass = 'bids';

}