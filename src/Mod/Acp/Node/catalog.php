<?php

namespace Verba\Mod\Acp\Node;


class catalog extends \Verba\Mod\Acp\Node{
    public $acpNodeType = 'catalog';
    public $ot = 'catalog';
    //public $titleLangKey = 'catalog acp node name';
    public $itemData = array('itemsType');

    function tabsets(){
        return [
            'default' => 'Catalog',
        ];
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
