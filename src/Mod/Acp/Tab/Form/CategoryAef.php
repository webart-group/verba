<?php

namespace Verba\Mod\Acp\Tab\Form;

class CategoryAef extends AEForm
{
    public $button = array(
        'title' => 'category acp tab form'
    );
    public $ot = 'catalog';
    public $url = '/acp/crud/catalog/cuform';
    public $instanceOf = array('type' => 'node');
}
