<?php

namespace Verba\Mod\Index\Block;

class Content extends \Verba\Block\Html
{
    public $templates = [
        'content' => 'index/content.tpl'
    ];

    public $css = ['index'];
    public $scripts = ['index'];

    public function prepare()
    {
        $this->getBlockByRole('HtmlBody')->addCssClass('index');
        parent::prepare();
    }
}
