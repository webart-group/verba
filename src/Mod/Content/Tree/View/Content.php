<?php

namespace Verba\Mod\Content\Tree\View;

class Content extends \Tree\Node\View
{

    public $url_prefix = '';

    function prepare()
    {
        if (!empty($this->item['extra_css_class'])) {
            $this->addCssClass($this->item['extra_css_class']);
        }

        $this->tpl_vars['title'] = $this->item['title'];
        $this->tpl_vars['url'] = $this->url_prefix . '#' . $this->item['id_code'];
    }
}
