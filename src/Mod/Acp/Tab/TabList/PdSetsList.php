<?php

namespace Verba\Mod\Acp\Tab\TabList;


class PdSetsList extends \Verba\Mod\Acp\Tab\TabList
{
    public $button = array(
        'title' => 'pd_set tab list'
    );
    public $ot = 'pd_set';
    public $action = 'list';
    public $url = '/acp/h/pd_set/list';

    function states()
    {
        $r = parent::states();
        $r['editlistobject'] = array(
            'type' => 'tabset',
            'name' => 'PdSetUpdate',
        );
        return $r;
    }

}
