<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Menu;

class Info extends \Verba\Block\Html {

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/menu/info/content.tpl',
    );

    function init(){
        // add Exit and Acp Links to Personal Menu

        $this->addItems(array(

            'INFO_MENU' => new Info\Center($this),

            'LANG_SELECTOR' => new \Mod\Profile\Block\Toolbar\Dropdown\Group($this,array(
                'items' => array(
                    'CONTENT' => new \langu_publicSelector($this,array(
                        'templates' => array(
                            'content' => 'layout/local/lang-selector/wrap.tpl',
                            'item' => 'layout/local/lang-selector/item.tpl',
                        ),
                    ))
                ),
            )),

            'BOTTOM_USER_LINKS' => new \Mod\Profile\Block\Toolbar\Dropdown\Group($this,array(
                'items' => array(
                    'CONTENT' => new BasicActions($this)
                ),
            )),
        ));
    }
}