<?php

namespace Verba\Mod\Acp\Node;


class catalog extends \Verba\Mod\Acp\Node{
    public $acpNodeType = 'gamecatalog';
    public $ot = 'catalog';
    //public $titleLangKey = 'catalog acp node name';
    public $itemData = array('itemsType');

    function tabsets(){
        return array(
            'default' => 'GameCatalog',
        );
    }

    function menu(){
        return array(
            'addnewnode' => array(
                'title' => \Verba\Lang::get('acp nodemenu addnew'),
                'type' =>'tabset',
                'name' => 'NodeCreateCatalog'
            ),
            'deletenode' => array(
                'title' => \Verba\Lang::get('catalog acp node menu delete'),
                'cfg' => array(
                    'url_sfx' => '/catalog/remove'
                )
            )
        );
    }
}
