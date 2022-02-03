<?php

class content_block extends \Verba\Block\Html
{
    protected $modCode = 'content';

    public $templates = array(
        'content' => '/content/block/block.tpl',
        'title' => '/content/block/title.tpl',
        'text' => '/content/block/text.tpl'
    );

    public $parseTextAsTemplate = false;

    public $text;
    public $title;
    public $id;

    use \Verba\Block\Html\Element\Attribute\CssClass;

    function prepare()
    {
        if(!is_string($this->text) && !$this->id && $this->rq->getIid())
        {
            $this->id = $this->rq->getIid();
        }
    }

    function build()
    {

        if (!is_string($this->text) && $this->id) {

            $Item = \Verba\_mod($this->modCode)->OTIC()->getItem($this->id);
            if (is_object($Item)) {
                $this->text = $Item->text;
                if ($this->title === null) {
                    $this->title = $Item->title;
                }
                if (isset($Item->extra_css_class) && strlen($Item->extra_css_class)) {
                    $this->addCssClass($Item->extra_css_class);
                }
            }

        }

        if (!is_string($this->text)) {
            settype($this->text, 'string');
        }

        if ($this->title === false) {
            $this->tpl->assign('TITLE_BLOCK', '');
        } else {
            $this->tpl->assign(array(
                'TITLE_VALUE' => $this->title
            ));
            $this->tpl->parse('TITLE_BLOCK', 'title');
        }

        if ($this->text === false) {
            $this->tpl->assign('TEXT_BLOCK', '');
        } else {
            $this->tpl->assign('TEXT_BLOCK', (
            $this->parseTextAsTemplate
                ? $this->tpl->parse_template($this->text)
                : $this->text
            ));
        }

        $this->tpl->assign(array(
            'ID_TAG_ATTR' => $this->id ? ' data-id="' . htmlspecialchars($this->id) . '"' : '',
            'EXTRA_CSS_CLASS' => count($this->cssClass)
                ? ' ' . $this->implodeCssClass()
                : '',
        ));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }
}
