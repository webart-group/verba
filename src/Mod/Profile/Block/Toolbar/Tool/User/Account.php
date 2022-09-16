<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool\User;

class Account extends \Verba\Mod\Profile\Block\Toolbar\Tool\User{

    public $url = '/profile/accounts';

    public $templates = array(
        'content' => 'profile/toolbar/tool/account.tpl',
    );
    public $code = 'accounts';
    public $cssClass = 'my-accounts';

    public $badge = array(
        'color' => 'white',
    );
    public $tplvars = array(
        'ACCOUNT_VALUE' => '',
        'ACCOUNT_CURR_CODE' => '',
    );

    public $icon = false;

    function prepare()
    {


        if(!$this->U->getAuthorized()){
            $this->content = '';
            return $this->content;
        }

        $this->url = \Verba\_mod('user')->getProfileUrl().'/accounts';
        parent::prepare();

        /**
         * @var $mCart \Verba\Mod\Cart
         * @var $mShop \Verba\Mod\Shop
         */
        $mCart = \Verba\_mod('cart');
        $mShop = \Verba\Mod\Shop::getInstance();
        $cartCur = $mCart->getCart()->getCurrency();
        /**
         * @var $Acc \Verba\Mod\Account\Model\Account
         */

        $summ = 0;
        foreach($this->U->Accounts()->getAccounts() as $Acc){
            if(!$Acc->active){
                continue;
            }
            $accSum = $Acc->getFullBalanceSum();
            $summ += $mShop->convertCur($accSum, $Acc->getCurrency()->getId(), $cartCur->getId());
        }

        $this->tpl->assign(array(
            'ACCOUNT_VALUE' => $cartCur->round($summ),
            'ACCOUNT_CURR_CODE' => $cartCur->symbol,
        ));

    }
}