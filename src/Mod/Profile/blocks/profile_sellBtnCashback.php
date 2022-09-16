<?php

class profile_sellBtnCashback extends profile_sellBtn
{

    public $scripts = array(
        array('sell_buttons', 'profile/tools'),
    );

    public $component = 'sell_btn_cashback';
    public $code = 'cashback';

    function init()
    {

        parent::init();

        $this->script = (new \Url(SYS_JS_URL . '/profile/tools/sell_buttons.js'))->get(true);
        /**
         * @var $mProfile \Verba\Mod\Profile
         */
        $mProfile = \Verba\_mod('profile');

        $this->ui = new \Html\Button(array(
            'classes' => 'btn small cashback-btn btn-grey',
            'attr' => array(
                'data-role' => 'ui-tool-trigger',
                'data-url' => $mProfile->getSellActionUrl($this->Order, $this->code),
                'data-confirm' => \Verba\Lang::get('profile orders sell workers ' . $this->code . ' confirm'),
            ),
            'value' => \Verba\Lang::get('game order buttons ' . $this->code),
        ));

    }
}
