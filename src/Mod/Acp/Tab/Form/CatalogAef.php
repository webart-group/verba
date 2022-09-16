<?php

namespace Verba\Mod\Acp\Tab\Form;

class CatalogAef extends AEForm
{
    public $button = [
        'title' => 'catalog acp tab title'
    ];
    public $ot = 'catalog';
    public $url = '/acp/h/catalog/cuform';
    public $instanceOf = array('type' => 'node');
}
