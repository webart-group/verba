<?php

namespace Verba\Mod\Faq\Tree\View;

class ContentFaqOnPage extends ContentFaq
{
    public $tplSharedKey = 'tnv_content_faq_onpage';

    protected static $_templates_defined;

    function init()
    {
        $this->templates = array_replace_recursive($this->templates, array('body' => '/faq/index/node-body.tpl'));

        parent::init();

        $this->addCssClass('faq-entry');
    }

    function prepare()
    {
        parent::prepare();
        $this->tpl_vars['text'] = $this->item['text'];
        $this->tpl_vars['id_code'] = $this->item['id_code'];
    }
}