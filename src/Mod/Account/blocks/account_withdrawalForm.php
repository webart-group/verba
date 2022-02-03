<?php

class account_withdrawalForm extends \Verba\Mod\Routine\Block\Form
{

    public $contentType = 'json';

    function prepare()
    {
        /**
         * @var $Acc \Mod\Account\Model\Account
         */
        $Acc =\Verba\User()->Accounts()->getAccount($this->rq->getParam('accId', true));

        if (!$Acc) {
            throw  new \Verba\Exception\Building('Unknown object');
        }

        $this->rq->action = 'new';

        $_cur = \Verba\_oh('currency');
        /**
         * @var $cur \Verba\Model\Currency
         */
        $cur = \Verba\_mod('currency')->getCurrency($Acc->currencyId);

        if (!$cur) {
            throw  new \Verba\Exception\Building('Bad acc currency');
        }

        $formTitle = \Verba\Lang::get('withdrawal form title new', array(
            'currencyCode' => strtoupper($cur->getValue('code')),
            'account' => $Acc->account,
        ));

        $this->cfg = 'public public/profile/withdrawal';
        $this->dcfg = array(
            'fields' => array(
                'prequisiteId' => array(
                    'parents' => array(
                        \Verba\_oh('user')->getID() => array(User()->getID()),
                        $_cur->getID() => array($cur->getId()),
                    ),
                ),
                'sum' => array(
                    'value' => $Acc->getBalanceSum(),
                    'extensions' => array(
                        'items' => array(
                            'priceUnit' => array(
                                'currencyId' => $Acc->currencyId,
                            ),
                            'withdrawalSum' => array(
                                'accId' => $Acc->id,
                                'avaible_sum' => $Acc->getBalanceSum(),
                                'curId' => $Acc->currencyId,
                            ),
                        )
                    ),
                ),
                'accountId' => array(
                    'value' => $Acc->id,
                ),
            ),
            'title' => array(
                'value' => $formTitle,
            ),
            'extendedData' => array(
                'Acc' => $Acc,
            ),
        );
    }
}
