<?php

namespace Verba\Mod\Acp\Tab\TabList;


class CategoryItems extends \Verba\Mod\Acp\Tab\TabList{
    public $button = array(
        'title' => '? acp list title'
    );
    public $ot;
    public $action = 'list';
    public $url = '/acp/h/?/list';
    public $linkedTo = array('type' => 'node');
    public $maxLevel = 1;
    public $currentLevel = 0;
//    public $contentTitleSubst = array(
//        'pattern' => 'products acp contentTitle productsByCatalog',
//    );

    function __construct($cfg = null)
    {
        $oh = \Verba\_oh($cfg['ot']);
        $this->url = str_replace('?', $oh->getCode(), $this->url);

        $this->button['title'] = str_replace('?', $oh->getCode(), $this->button['title']);

        parent::__construct($cfg);
    }

}
