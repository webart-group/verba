<?php
namespace Verba\Mod\Acp\Block;

class Login extends \Verba\Block\Html {

    public $templates = [
        'content' => 'acp/login/login.tpl'
    ];

    function init() {

        $this->items = array(
            'AUTH_FORM' => new \Verba\Mod\User\Block\Login\Form($this)
        );

        $this->mergeHtmlIncludes(new \page_htmlIncludesFormFull($this));

        $this->addCss(array(
            array('form style login-form', 'acp'),
            array('starsky', '/js/starsky'),
        ), 500);

        $this->addScripts(array(
            array('commonUI', 'common'),
            array('form formValidator', 'form'),
            array('publicUIGuest loginFormCtrl', 'common'),
            array('starsky', '/js/starsky'),
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