<?php
namespace Verba\Mod\Profile\Block\Toolbar;


use \Verba\Mod\Profile\Block\Toolbar\Dropdown\Button\User as ButtonUser;
use \Verba\Mod\Profile\Block\Toolbar\Dropdown\Button\Guest as ButtonGuest;

use \Verba\Mod\Profile\Block\Toolbar\Dropdown\Body\User as BodyUser;
use \Verba\Mod\Profile\Block\Toolbar\Dropdown\Body\Guest as BodyGuest;

/**
 * Обертка меню
 *
 */
class Dropdown extends \Verba\Block\Html {

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/content.tpl',
    );

    public $tplvars = array(
        'BUTTON_CONTENT' => '',
        'TOP' => '',
        'BOTTOM' => '',
        'SIGN_CLASS' => '',
    );

    function init(){

        $isLoggedIn =\Verba\User()->getAuthorized();

        $className = $isLoggedIn ? 'User' : 'Guest';

        $button = '\Mod\Profile\Block\Toolbar\Dropdown\Button\\'.$className;
        $body = '\Mod\Profile\Block\Toolbar\Dropdown\Body\\'.$className;

        $this->addItems([
            'BUTTON' => new $button($this),
            'BODY' => new $body($this),
            'BOTTOM' => new Dropdown\Menu\Info($this)
        ]);

        $this->tpl->assign([
            'SIGN_CLASS' =>\Verba\User()->getAuthorized() ? ' logged' : ' unlogged'
        ]) ;
    }
}
