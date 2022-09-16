<?php
namespace Verba\Mod\Profile\Block\Toolbar\Dropdown;

class Body extends \Verba\Block\Html {

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/body.tpl',
    );

    public $tplvars = [
        'COMMON_MENU' => '',
    ];

}
