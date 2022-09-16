<?php
namespace Verba\Mod\Profile\Block\Toolbar\Dropdown;

class Menu extends \menu_Line{

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/menu/wrap.tpl',
        'item' => 'profile/toolbar/dropdown/menu/item.tpl',
    );

    public $lastItem = false;
}
