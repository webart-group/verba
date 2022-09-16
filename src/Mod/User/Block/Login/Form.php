<?php
namespace Verba\Mod\User\Block\Login;

class Form extends \Verba\Block\Html
{

    public $templates = array(
        'content' => '/user/login/login_form.tpl'
    );

    public $initState = 'login';// 'login' | 'registration' | 'specify';
    public $placement = 'inline'; // 'inline'| 'modal'
    protected $defaultState = 'registration';

    function build()
    {
        /**
         * @var $mUser \Verba\Mod\User
         */
        $mUser = \Verba\_mod('user');

        $formVariants = array(
            'login' => array(
                'url' => $mUser->getAuthorizationUrl(),
                'alt' => 'registration',
            ),
            'registration' => array(
                'url' => $mUser->getRegisterUrl(),
                'alt' => 'login',
            ),
            'specify' => array(
                'url' => $mUser->getSpecifyUrl(),
            ),
        );

        $initState = $this->rq->getParam('state');
        if (!$initState) {
            $initState = $this->initState;
        }
        if (!is_string($initState) || !in_array($initState, array_keys($formVariants))) {
            $initState = $this->defaultState;
        }

        $jsCfg = array(
            'formVariants' => $formVariants,
            'placement' => $this->placement,
            'states' => array(
                'prev' => false,
                'cur' => $initState,
                'next' => false,
            ),
            'llang' => \Verba\Lang::get('user auth'),
        );

        $this->tpl->assign(array(
            'JS_CFG' => json_encode($jsCfg, JSON_FORCE_OBJECT),
            'PASSWORD_RESTORE_URL' => $mUser->getLostpasswordUrl(),
        ));

        // Captcha
//        $cap = new \captcha_fe($this->rq);
//        $cap->run();

//        $this->tpl->assign('CAPTCHA', $cap->content);

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }

    function getTitle(){
        return '';
    }
}
