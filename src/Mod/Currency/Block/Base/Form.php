<?php

namespace Verba\Mod\Currency\Block\Base;

class Form extends \Verba\Block\Html
{

    function build()
    {
        $mCur = \Verba\_mod('currency');

        $this->tpl->define(array(
            'currencybaseform' => 'shop/currency/basecurrencyform/form.tpl'
        ));
        $sl = new \Verba\Html\Select(array('name' => 'basecurrencyid'));
        $values = array();
        $_cur = \Verba\_oh('currency');
        $curPac = $_cur->getPAC();

        $currencies = $mCur->getCurrency();

        if (is_array($currencies) && count($currencies)) {
            foreach ($currencies as $cid => $cdata) {
                $values[$cid] = $cdata->p('title');
                if ($cdata->p('isbase') == 1) {
                    $sl->setValue($cid);
                }
            }
        }
        $sl->setValues($values);
        $this->tpl->assign(array('ACP_BASECURRENCY_SELECT' => $sl->build()));
        $this->content = $this->tpl->parse(false, 'currencybaseform');
        return $this->content;
    }
}
