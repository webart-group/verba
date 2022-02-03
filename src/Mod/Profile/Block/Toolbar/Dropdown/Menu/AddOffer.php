<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Menu;

class AddOffer extends \Verba\Block\Html {
    public $templates = array(
        'content' => 'profile/toolbar/dropdown/menu/add_offer/content.tpl',
    );

    function prepare(){
        $this->tpl->assign(array(
            'URL' => '/sell',
        ));
    }
}
