<?php

namespace Verba\Mod\Menu\Tree\View;

use \Tree\Node\View as TreeNodeView;

class Menu extends TreeNodeView
{
    public $tplSharedKey = 'tnv_menu';

    function prepare()
    {
        if (!empty($this->item['css_class']) && !$this->skipBodyClass) {
            $this->addCssClass($this->item['css_class']);
        }

        $this->parse_attrs = array(
            'url', 'title'
        );
    }
}
