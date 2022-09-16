<?php

namespace Verba\Mod\Acp\Tab\Form;

class MetaAef extends AEForm
{
    public $button = array(
        'title' => 'meta acp tab_aef title'
    );
    public $ot = 'meta';
    public $url = '/acp/h/meta/cuform';
    public $linkedTo = array('type' => 'tab');
}
