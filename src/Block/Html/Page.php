<?php
namespace Verba\Block\Html;

class Page extends \Verba\Block\Html
{
    public $templates = [
        'content' => 'page/page.tpl',
    ];

    public $compile_js = false;
    public $compile_css = false;

    public $role = 'HtmlPage';

    public $items = [
        'BODY' => false,
        'HEAD' => false,
    ];

    function init(){
        global $S;
        $is_compile_js = $S->gC('js_compile');
        if($is_compile_js !== null){
            $this->compile_js = (bool)$is_compile_js;
        }

        $is_compile_css = $S->gC('css_compile');
        if($is_compile_css !== null){
            $this->compile_css = (bool)$is_compile_css;
        }
    }

    function route()
    {
        if(!$this->getItem('BODY')){
            $this->addItems(['BODY' => new Page\Body($this)]);
        }

        if(!$this->getItem('HEAD')){
            $this->addItems(['HEAD' => new Page\Head($this)]);
        }

        $response = new \Verba\Response\Html($this);
        $response->addItems($this);
        return $response;
    }

    function prepare(){
        $this->tpl->assign(array(
            'LANGUAGE' => SYS_LOCALE,
        ));
    }


}
