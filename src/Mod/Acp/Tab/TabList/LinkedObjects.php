<?php

namespace Verba\Mod\Acp\Tab\TabList;


class LinkedObjects extends \Verba\Mod\Acp\Tab\TabList
{
    public $button = array(
        'title' => '',//key to linked objects title
    );
    public $ot = ''; // ot
    public $action = 'list';
    public $url = '';// url to load list
    public $linkedTo = array('type' => 'tab', 'id' => 'ListObjectForm');
}
