<?php

namespace Verba\Mod\Infocenter\Tree\View;

use \Verba\Mod\Menu\Tree\View\Menu as MenuView;

class Menu extends MenuView
{
    public $tplSharedKey = 'tnv_menu';

    function isParseBody()
    {
        if ($this->item['css_class'] == '_group_wrap' || $this->item['hidden']) {
            return false;
        }
        return parent::isParseBody();
    }

    function getBodyTplAlias()
    {
        return $this->item['css_class'] == '_col'
            ? 'body_no_link'
            : parent::getBodyTplAlias();
    }
}
