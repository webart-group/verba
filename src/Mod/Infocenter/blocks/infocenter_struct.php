<?php

class infocenter_struct extends \Verba\Block\Html
{
    function build()
    {
        $_menu = \Verba\_oh('menu');
        $treeCfg = array(
            'nodeTypes' => array(
                'menu' => '\Mod\Infocenter\Tree\View\Menu',
            ),
            'levelsCfg' => array(
                1 => array(
                    'skipBody' => true,
                )
            )
        );

        $Tree = new Tree($_menu, 316, 3, array($_menu->getID()));
        $Tree->applyConfigDirect($treeCfg);

        /**
         * @var $Node \Mod\Infocenter\Tree\View\Menu
         */
        $Node = $Tree->buildNodesTree();

        $this->content = $Node->parse();
        $Node->tpl()->clearShared();

        return $this->content;
    }
}
