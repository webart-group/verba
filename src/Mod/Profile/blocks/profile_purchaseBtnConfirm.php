<?php

class profile_purchaseBtnConfirm extends profile_purchaseBtn
{

    public $scripts = array(
        array('purchase_btn_confirm', 'profile/tools'),
    );

    public $script = '/profile/tools/purchase_btn_confirm.js';
    public $component = 'purchase_btn_confirm';
    public $code = 'confirm';

    function init()
    {

        parent::init();

        /**
         * @var $mProfile Profile
         */
        $mProfile = \Verba\_mod('profile');

        $this->ui = new \Html\Button(array(
            'classes' => 'btn purchase-btn light btn-orange',
            'attr' => array(
                'data-role' => 'ui-tool-trigger',
                'data-url' => $mProfile->getPurchaseActionUrl($this->Order, $this->code),
                'data-confirm' => \Verba\Lang::get('profile orders purchase workers ' . $this->code . ' confirm'),
            ),
            'value' => \Verba\Lang::get('game order buttons title_purchase'),
        ));

    }
}
