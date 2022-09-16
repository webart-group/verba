<?php

class faq_struct extends \Verba\Block\Html
{
    function build()
    {
        $_menu = \Verba\_oh('menu');
        $_cnt = \Verba\_oh('content');

        $treeCfg = array(
            'nodeTypes' => array(
                'menu' => '\Mod\Infocenter\Tree\View\Menu',
                'content' => '\Mod\Faq\Tree\View\ContentFaq',
            ),
        );

        $Tree = new Tree($_menu, 320, 1, array($_cnt->getID(), $_menu->getID()));
        $Tree->applyConfigDirect($treeCfg);
        /**
         * @var $Node \Verba\Mod\Infocenter\Tree\View\Menu
         */
        $Node = $Tree->buildNodesTree();

        $this->content = $Node->parse();
        $Node->tpl()->clearShared();
        unset($Node);
        unset($Tree);

        return $this->content;

    }
}
