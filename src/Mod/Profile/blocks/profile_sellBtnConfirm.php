<?php

class profile_sellBtnConfirm extends profile_sellBtn
{

    public $scripts = array(
        array('sell_buttons', 'profile/tools'),
    );

    public $component = 'sell_btn_confirm';
    public $code = 'confirm';

    function init()
    {

        parent::init();

        $this->script = (new \Url(SYS_JS_URL . '/profile/tools/sell_buttons.js'))->get(true);
        /**
         * @var $mProfile Profile
         */
        $mProfile = \Verba\_mod('profile');

        if (!$this->Order->confirmedSeller) {
            $this->ui = new \Html\Button([
                'classes' => 'btn sell-btn btn-blue',
                'attr' => array(
                    'data-role' => 'ui-tool-trigger',
                    'data-url' => $mProfile->getSellActionUrl($this->Order, $this->code),
                    'data-confirm' => \Verba\Lang::get('profile orders sell workers ' . $this->code . ' confirm'),
                ),
                'value' => \Verba\Lang::get('game order buttons title_sell'),
            ]);
        } else {
            $this->ui = false;
            $this->state = 'confirmed';
        }

    }
}
