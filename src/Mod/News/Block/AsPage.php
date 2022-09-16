<?php
namespace Verba\Mod\News\Block;

class AsPage extends \Verba\Mod\Content\Block\AsPage
{

    public $templates = [
        'content' => '/content/as-page/content.tpl',
        'title' => '/content/as-page/title.tpl',
        'text' => '/content/as-page/text.tpl'
    ];

    protected $modCode = 'news';
}
