<?php

namespace Mod\User\Block;

class Login extends \Verba\Block\Html{

    public $templates = [
        'content' => '/user/login/content.tpl',
    ];

    function route()
    {
        $handler = new \Layout\Minimal($this->request);
        $handler->addItems(array(
            'CONTENT' => $this
        ));

        return $handler->route();
    }

    function init()
    {
        $this->items = array(
            'AUTH_FORM' => new Login\Form($this)
        );

        $this->mergeHtmlIncludes(new \page_htmlIncludesCore($this));

        $this->mergeHtmlIncludes(new \page_htmlIncludesFormFull($this));

        $this->addCss(array(
            array('modal'),
            array('form'),
            array('commonUI'),
            array('form style login-form', 'acp'),
        ), 500);

        $this->addScripts(array(
            array('commonUI', 'common'),
            array('form formValidator', 'form'),
            array('publicUIGuest loginFormCtrl', 'common'),
        ), 500);
    }

    function prepare(){
        \Verba\Hive::setBackURL();

        $this->addJsBefore("
window.CUI = new commonUI();
window.CUI.render();
");

        $this->tpl->assign(array(
            'THIS_HOST' => SYS_THIS_HOST,
        ));
    }
}
