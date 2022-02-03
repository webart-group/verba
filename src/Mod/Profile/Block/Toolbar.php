<?php
namespace Mod\Profile\Block;

/**
 * Тулбар
 *
 */
class Toolbar extends \Verba\Block\Html {

    public $templates = array(
        'content' => '/profile/toolbar/content.tpl',
    );

    function init(){
        $this->addItems(array(
            'DROPDOWN' => new Toolbar\Dropdown($this),
        ));
    }
}
