<?php
namespace Mod\Content\Block;

class AsPage extends \content_block
{

    public $templates = array(
        'content' => '/content/as-page/content.tpl',
        'title' => '/content/as-page/title.tpl',
    );

    public $cssClass = ['content-as-page'];
    public $css = ['as-page', 'content'];

    public function prepare()
    {
        parent::prepare();
        $this->getBlockByRole('HtmlBody')->addCssClass('content-as-page');
    }

}
