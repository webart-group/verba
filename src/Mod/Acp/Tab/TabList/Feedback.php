<?php

namespace Verba\Mod\Acp\Tab\TabList;


use Verba\Mod\Acp\Tab\TabList;

class Feedback extends TabList
{
    public $button = [
        'title' => 'feedback acp tab list'
    ];
    public $ot = 'feedback';
    public $action = 'list';
    public $url = '/acp/h/feedback/list';
}
