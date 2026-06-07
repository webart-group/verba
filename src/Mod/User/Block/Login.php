<?php

namespace Verba\Mod\User\Block;

use Verba\Block\Html;

class Login extends Html{

    public $templates = [
        'content' => '/page/user/login/content.tpl',
    ];

    function init()
    {
        $this->items = array(
            'AUTH_FORM' => new Login\Form($this)
        );
//        $this->addHeadTag('script', [
//            'type' => 'module',
//            'crossorigin' => '',
//            'src' => 'js/login-page.js',
//        ], '');
        $this->addHeadTag('link', [
            'rel' => 'modulepreload',
            'crossorigin' => '',
            'href' => 'js/tabs.js',
        ]);

        $this->addCss([
            ['tabs login-page'],
        ], 500);

//
//        $this->addCss([
//            ['modal'],
//            ['form'],
//            ['commonUI'],
//            ['form style login-form', 'acp'],
//        ], 500);

        $this->addScripts(array(
            // array('commonUI', 'common'),
            array('form formValidator', 'form'),
            //array('publicUIGuest', 'common'),
            array('loginFormCtrl', 'common'),
        ), 500);
    }

    function prepare(){
        \Verba\Hive::setBackURL();

        /**
         * @var Html\Page\Body $htmlBody
         */
        $htmlBody = $this->getBlockByRole('HtmlBody');
        $htmlBody->addCssClass('page-login');

        $this->getBlockByRole('layout')->setTplvars(['PAGE_TYPE' => 'auth']);
//
//        $this->addJsBefore("
//window.CUI = new commonUI();
//window.CUI.render();
//");

        $this->tpl->assign(array(
            'THIS_HOST' => SYS_THIS_HOST,
        ));
    }
}
