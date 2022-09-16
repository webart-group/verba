<?php

namespace Verba\Mod\Acp\Tab\Form;

class MenuAef extends AEForm
{
    public $viewName = 'Menu';
    public $button = array(
        'title' => 'menu acp tab title'
    );
    public $ot = 'menu';
    public $url = '/acp/h/menu/cuform';
    public $inheritUrl = false;
    public $instanceTo = array('type' => 'node');
}
