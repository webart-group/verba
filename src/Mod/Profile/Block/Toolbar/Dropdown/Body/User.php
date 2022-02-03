<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Body;

use Mod\Profile\Block\Toolbar\Dropdown\Menu\Common;

class User extends \Verba\Mod\Profile\Block\Toolbar\Dropdown\Body {
    function init(){
        $this->addItems(array(
            'COMMON_MENU' => new Common($this)
        ));
    }
}
