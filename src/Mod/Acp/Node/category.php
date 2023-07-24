<?php

namespace Verba\Mod\Acp\Node;



class Category extends \Verba\Mod\Acp\Node
{

    public $ot = 'catalog';
    public $acpNodeType = 'category';
    public $itemData = array('itemsType');

    function tabsets()
    {
        return array(
            'default' => 'Category',
        );
    }

    function menu()
    {
        return [
            'addnewnode' => array(
                'title' => \Verba\Lang::get('acp nodemenu addnew'),
                'type' =>'tabset',
                'name' => 'NodeCreateCategory'
            ),
            'deletenode' => array(
                'title' => \Verba\Lang::get('catalog acp node menu delete'),
                'cfg' => array(
                    'url_sfx' => '/catalog/remove'
                )
            )
        ];
    }
}

