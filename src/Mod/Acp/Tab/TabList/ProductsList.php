<?php

namespace Verba\Mod\Acp\Tab\TabList;


use Verba\Mod\Acp\Tab\TabList;

class ProductsList extends TabList
{
    public $button = array(
        'title' => 'products acp tab list'
    );
    public $ot;
    public $action = 'list';
    public $url = '/acp/h/product/list';
    public $linkedTo = array('type' => 'tab', 'id' => 'CatalogAef');
    public $maxLevel = 1;
    public $currentLevel = 0;
    public $contentTitleSubst = array(
        'pattern' => 'products acp contentTitle productsByCatalog',
    );

    function states()
    {
        $r = array(
            'addlistobject' => array(
                'type' => 'tabset',
                'name' => 'ProductCreate',
            ),
            'editlistobject' => array(
                'type' => 'tabset',
                'name' => 'ProductUpdate',
                'cfg' => array(
                    'maxLevel' => $this->maxLevel,
                    'currentLevel' => $this->currentLevel + 1,
                )
            )
        );
        return $r;
    }
}
