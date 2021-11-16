<?php
namespace Verba\Block\Html\Page;

class Body extends \Verba\Block\Html
{
    public $templates = [
        'content' => 'page/body.tpl'
    ];

    public $tplvars = [
        'CLASS_ATTR' => '',
        'JS_BEFORE' => '',
        'PAGE_GLOBAL_JS_AFTERBODY' => '',
        'PAGE_GLOBAL_CSS_AFTERBODY' => '',
        'JS_AFTER' => '',
    ];

    public $items = [
        'LAYOUT' => false,
    ];

    public $role = 'HtmlBody';

    use \Verba\Block\Html\Element\Attribute\CssClass;

    /**
     * @return \Block\Html|\Response\Html
     */
    function route()
    {
        $HtmlPage = new \Verba\Block\Html\Page($this);

        $HtmlPage->addItems([
            'BODY' => $this,
        ]);

        return $HtmlPage->route();
    }

    function build()
    {
        if(count($this->cssClass)){
            $this->tpl->assign('CLASS_ATTR', ' class="'.implode(' ',$this->cssClass).'"');
        }

        $this->tpl->assign([
            'JS_BEFORE' => count($this->jsBefore) ? \Verba\Response\Json::addWhenDocumentReady($this->jsBefore) : '',
            'JS_AFTER' => count($this->jsAfter) ? \Verba\Response\Json::addWhenDocumentReady($this->jsAfter) : '',
        ]);

        return parent::build();
    }

}
