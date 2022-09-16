<?php

class infocenter_page extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'ic/page/content.tpl'
    );

    public $css = array(
        array('ic')
    );

    public $css_class = array();
    public $content_title = null;
    public $content_body = null;

    protected $menuNode = null;

    function init()
    {

        $this->addItems(array(
            'IC_MENU' => new infocenter_menu($this),
        ));

    }

    function prepare()
    {

        /**
         * @var $mMenu \Verba\Mod\Menu
         */
        $mMenu = \Verba\_mod('menu');
        $this->menuNode = $mMenu->getActiveNode();

        if (!$this->menuNode) {
            throw new \Verba\Exception\Routing();
        }

        $_cnt = \Verba\_oh('content');

        $_menu = \Verba\_oh('menu');
        $qm = new \Verba\QueryMaker($_cnt, false, true);
        $qm->addConditionByLinkedOT($_menu, $this->menuNode['id']);
        $qm->addOrder(array('priority' => 'd'));
        $qm->addWhere(1, 'active');
        $sqlr = $this->DB()->query($qm->getQuery());
        if ($sqlr && $sqlr->getNumRows()) {

            $isFirstItem = true;
            $b = new content_block($this);
            while ($item = $sqlr->fetchRow()) {
                if ($isFirstItem) {
                    $b->addCssClass('frst-entry');
                    $isFirstItem = false;
                } else {
                    $b->clearCssClass();
                }
                $b->title = $item['title'];
                $b->text = $item['text'];
                if (!empty($item['extra_css_class'])) {
                    $b->addCssClass($item['extra_css_class']);
                }
                $b->id = $item['id'];
                $this->content_body .= $b->build();
            }
        }
    }

    function build()
    {

        $this->tpl->assign(array(
            'IC_CONTENT_EXTRA_CLASS' => !empty($this->cssClass) ? ' ' . implode(' ', $this->cssClass) : '',
            'IC_CONTENT' => $this->content_body,
        ));

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }

}

?>