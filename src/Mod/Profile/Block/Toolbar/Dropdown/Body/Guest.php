<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Body;

use Html\Div;
use Mod\Profile\Block\Toolbar\Dropdown\Menu\Common;

class Guest extends \Verba\Mod\Profile\Block\Toolbar\Dropdown\Body {


    function init(){

        $args = [
            'templates' => [
                'content' => 'profile/toolbar/dropdown/menu/basic/login.tpl',
            ],
            'tplvars' => ['LOGIN_URL' => \Verba\User\User::i()->getLoginPageUrl()]
        ];

        $b = new \Verba\Block\Html($this, $args);

        $this->addItems(array(
            'COMMON_MENU' => (new \Verba\Block\Html($this, ['items' => [

                $b,

                new \Verba\Block\Html($this, [
                    'templates' => [
                        'content' => 'profile/toolbar/dropdown/menu/basic/registration.tpl',
                    ],
                    'tplvars' => ['REGISTRATION_URL' => \Verba\User\User::i()->getLoginPageUrl()]
                ]),
            ]]))
        ));
    }

}
