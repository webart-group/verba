<?php

namespace Mod\Profile\Block\Accounts;

class Tab extends \profile_contentCommon
{

    public $titleLangKey = null;

    public $templates = array(
        'content' => '/profile/accounts/tab.tpl',
        'accounts' => '/profile/accounts/list.tpl',
        'prequisites' => '/profile/prequisites/list.tpl'
    );

    public $coloredPanelCfg = false;

    public $scripts = array(
        array('accounts', 'profile'),
        array('prequisites', 'profile'),
        array('withdrawal', 'profile'),
    );

    public $css = array(
        array('accounts', 'profile'),
        //array('prequisites', 'profile'),
    );

    public $bodyClass = 'profile-accounts';

    function build()
    {

        if (is_string($this->content)) {
            return $this->content;
        }

        $this->mergeHtmlIncludes(new \page_htmlIncludesForm($this));


        // ### Панель Кошельки

        $accs = $this->U->Accounts()->getAccountsAsArrays(false, true);
        $_acc = \Verba\_oh('account');
        $accListCfg = array(
            'ot_id' => $_acc->getID(),
            'feats' => array(
                'edit' => 0,
            ),
            'url' => array(
                'update' => '/profile/accounts/list/update'
            ),
            'items' => $accs,
            'lang' => \Verba\Lang::get('account warns'),
        );

        $this->tpl->assign(array(
            'ACCOUNTS_LIST_CFG' => json_encode($accListCfg, JSON_FORCE_OBJECT),
        ));

        $accPan = new \page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('account title'),
            'content' => $this->tpl->parse(false, 'accounts'),
            'scheme' => 'green'
        ));

        $accPan->prepare();
        $accPan->build();


        // ### Панель Реквизиты

        $_preq = \Verba\_oh('prequisite');

        $qm = new \Verba\QueryMaker($_preq);
        $qm->addWhere($this->U->getId(), 'owner');
        $mCurrency = \Mod\Currency::getInstance();
        $sqlr = $qm->run();
        $preqs = array();
        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $Cur = $mCurrency->getCurrency($row['currencyId']);
                if (!$Cur || $Cur->hidden == 1) {
                    continue;
                }
                $row['active'] = $row['active'] * 1;
                $preqs['i' . $row['id']] = $row;
            }
        }
        $preqListCfg = array(
            'ot_id' => $_preq->getID(),
            'url' => array(
                'cuform' => '/profile/prequisites/list/cuform',
                'create' => '/profile/prequisites/list/create',
                'update' => '/profile/prequisites/list/update',
                'remove' => '/profile/prequisites/list/remove',
            ),
            'items' => $preqs,
        );

        // мультиселектор для панели счета
        $msCfg = array(
            'saveToSelector' => false,
            'saveUnits' => 'all',
            'units' => array(
                'name' => 'currencyId',
                'currentValue' => false,
                'eName' => 'currencyId',
                'url' => false,
                'emptyOptionAllowed' => false,
                'preload' => 'all',
                'valuesGenerator' => array(
                    'handler' => array($this, 'getCurrenciesForMultiSelector'),
                ),
                'children' => array(
                    'name' => 'paysysId',
                    'currentValue' => false,
                    'eName' => 'paysysId',//'parent['._oh('currency')->getID().']',
                    'url' => false,
                    'emptyOptionAllowed' => false,
                    'valuesGenerator' => array(
                        'handler' => array($this, 'getPaysysForMultiSelector'),
                        //'args' => is_numeric($serviceId) ? array($serviceId) : null,
                    ),
                    'preload' => 'all',
                )
            )
        );

        $ms = new \Verba\Block\Html\Form\MultiSelector(false, $msCfg);
        $ms->prepare();
        $ms->build();
        $this->mergeHtmlIncludes($ms);

        $this->tpl->assign(array(
            'PREQUISITES_LIST_CFG' => json_encode($preqListCfg, JSON_FORCE_OBJECT),
            'MP_SELECTOR' => $ms->content,
            'MUSE_E_SELECTOR' => $ms->gC('wrapSelector'),
        ));

        $preqPan = new \page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('prequisite title'),
            'content' => $this->tpl->parse(false, 'prequisites'),
            'scheme' => 'blue',
        ));

        \Verba\Lang::sendToClient('prequisite form validation');

        $preqPan->prepare();
        $preqPan->build();


        ### История запросов на вывод
        $b_withdrawal_hist = new \profile_withdrawalList($this, array('userId' => $this->U->getID()));
        $b_withdrawal_hist->prepare();
        $b_withdrawal_hist->build();
        $this->mergeHtmlIncludes($b_withdrawal_hist);


        ### История балансовых операций
        $_balop = \Verba\_oh('balop');
        $b_balops_hist = new \profile_balopsList(array('ot_id' => $_balop->getID()), array('userId' => $this->U->getID()));
        $b_balops_hist->run();
        $this->mergeHtmlIncludes($b_balops_hist);

        $this->tpl->assign(array(
            'ACCOUNTS_PANEL' => $accPan->content,
            'PREQUISITES_PANEL' => $preqPan->content,
            'WITHDRAWAL_LIST' => $b_withdrawal_hist->getContent(),
            'BALOPS_LIST' => $b_balops_hist->getContent(),
        ));

        $this->content = $this->tpl->parse(false, 'content');

        \Verba\Hive::setBackURL();

        return $this->content;
    }

    /** Multiselector */
    function getCurrenciesForMultiSelector($pids = false)
    {

        $k = current($pids); // always '.' as root node;
        $r = array($k => array());
        /**
         * @var $mCurrency Currency
         */
        $mCurrency = \Verba\_mod('currency');
        $allCurs = $mCurrency->getCurrency(false, true, true);

        foreach ($allCurs as $id => $cur) {
            $r[$k][$id] = array(
                'title' => $cur->getValue('title'),
                'id' => $id,
            );
        }

        return $r;
    }

    function getPaysysForMultiSelector($pids = false)
    {
        $r = array();

        $_cur = \Verba\_oh('currency');
        $_pay = \Verba\_oh('paysys');

        $cur_ot_id = $_cur->getID();
        $pay_ot_id = $_pay->getID();

        if (!is_array($pids) || !count($pids)) {
            return $r;
        }

        $br = \Verba\Branch::get_branch(array($_cur->getID() => array(
            'iids' => $pids,
            'aot' => $_pay->getID()
        )), 'down', 1, true, false, 'd', true, 'output');
        $paysData = array();
        if (is_array($br['handled'][$pay_ot_id]) && count($br['handled'][$pay_ot_id])) {
            $paysData = $_pay->getData($br['handled'][$pay_ot_id], true, array('title'));
        }


        foreach ($pids as $curId) {
            $r[$curId] = array();
            if (!isset($br['pare'][$cur_ot_id][$curId][$pay_ot_id])
                || !is_array($br['pare'][$cur_ot_id][$curId][$pay_ot_id])
                || !count($br['pare'][$cur_ot_id][$curId][$pay_ot_id])) {
                continue;
            }

            foreach ($br['pare'][$cur_ot_id][$curId][$pay_ot_id] as $payId) {
                $r[$curId][$payId] = array(
                    'id' => $payId,
                    'title' => isset($paysData[$payId]['title']) ? $paysData[$payId]['title'] : '??'
                );
            }
        }

        return $r;
    }
}

