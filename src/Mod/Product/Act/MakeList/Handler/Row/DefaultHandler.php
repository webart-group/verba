<?php

namespace Verba\Mod\Product\Act\MakeList\Handler\Row;

class DefaultHandler extends \Act\MakeList\Handler\Row
{
    static $c = 0;

    function run()
    {
        self::$c++;
        $tpl = $this->list->tpl();
        $oh = \Verba\_oh($this->list->row['ot_id']);
        $url = \Verba\Mod\Seo::idToSeoStr($this->list->row, array('seq' => $this->list->getCurrentPos(), 'slID' => $this->list->getID()));
        $iid = $this->list->row[$oh->getPAC()];
        $cur = \Verba\_mod('cart')->getCurrency();

        $variants = array(
            $iid => array(
                'size' => $this->list->row['size'],
                'price' => \Verba\reductionToCurrency($this->list->row['price'] * $cur->rate),
                'size_unit' => $this->list->row['size_unit'],
                'size_unit__value' => $this->list->row['size_unit__value'],
            ),
        );
        if (!empty($this->list->row['variant'])) {
            $cv = explode('#', $this->list->row['variant']);
            foreach ($cv as $v) {
                $v = explode(':', $v);
                $variants[$v[0]] = array(
                    'price' => \Verba\reductionToCurrency($v[1] * $cur->rate),
                    'size' => $v[2],
                    'size_unit' => $v[3],
                    'size_unit__value' => (string)$this->getSizeUnitPdValue($v[3]),
                );
            }
            uasort($variants, array(\Verba\_mod('product'), 'sortVariants'));
        } else {
            $this->list->rowClass .= ' one-variant';
        }
        if (!isset($tpl->FILELIST['variant-wrap'])) {
            $tpl->define(array(
                'variant-wrap' => '/product/list/variant/wrap.tpl',
                'variant-item' => '/product/list/variant/item.tpl',
            ));
        }
        $tpl->assign(array(
            'ITEM_CURRENCY_SHORT' => $cur->short,
            'ITEM_PROMOS' => $this->parsePromos(),
            'ITEM_PROMO_SIGN' => $this->parsePromoSign(),
        ));

        $tpl->clear_vars(array('VARIANTS_ITEMS'));
        $tpl->assign(array(
            'VARIANT_OT_ID' => $this->list->row['ot_id'],
        ));
        foreach ($variants as $vid => $cvar) {
            $tpl->assign(array(
                'VARIANT_ID' => $vid,
                'VARIANT_SIZE' => reductionToFloat($cvar['size']),
                'VARIANT_SIZE_UNIT' => (string)$cvar['size_unit__value'],
                'VARIANT_PRICE' => \Verba\reductionToCurrency($cvar['price']),
            ));

            $tpl->parse('VARIANTS_ITEMS', 'variant-item', true);
        }

        $tpl->parse('ITEM_VARIANTS', 'variant-wrap');

        $cc = (int)$this->list->row['comments_count'];
        if (!$cc) {
            $cc = $cw = '';
        } else {
            $cw = \Verba\make_padej_ru($cc, \Verba\Lang::get('comment word root'), \Verba\Lang::get('comment word padeji'));
        }
        reset($variants);
        $first_var = current($variants);
        $tpl->assign(array(
            'ITEM_COMMENTS_COUNT_VAL' => $cc,
            'ITEM_COMMENTS_WORD' => $cw,
            'ITEM_ARTICUL' => $this->list->row['articul'],
            'ITEM_BRANDID_VALUE' => $this->list->row['brandId__value'],
            'ITEM_PAGE_URL' => $url,
            'ITEM_BASE_PRICE' => $first_var['price'] > 0 ? \Verba\reductionToCurrency($first_var['price']) : '',
            '_ITEM_PRICE' => $first_var['price'] > 0 ? \Verba\reductionToCurrency($first_var['price'] * $cur->rate) : '',
            'ITEM_PRICE_UNIT' => $cur->short,
            'ITEM_OT' => $this->list->row['ot_id'],
            'ITEM_ID' => $iid,
            '_ITEM_SIZE' => reductionToFloat($first_var['size']),
            '_ITEM_SIZE_UNIT_TITLE' => $first_var['size_unit__value'],
        ));

        return $this->content;
    }

    function parsePromos()
    {
        if (!isset($this->list->row['promos']) || \mb_strlen($this->list->row['promos']) < 3) {
            return '';
        }
        $tpl = $this->list->tpl();
        if (!isset($tpl->FILELIST['promo-wrap'])) {
            $tpl->define(array(
                'promo-wrap' => '/product/list/promo/wrap.tpl',
                'promo-item' => '/product/list/promo/item.tpl',
            ));
        }
        $tpl->clear_vars(array('PROMO_ITEMS'));

        $promos = explode('~', $this->list->row['promos']);
        foreach ($promos as $promoasstr) {
            list($id, $title, $anno) = explode('^', $promoasstr);
            $tpl->assign(array(
                'PROMO_ITEM_ANNO' => !empty($anno) ? $anno : $title
            ));
            $tpl->parse('PROMO_ITEMS', 'promo-item', true);
        }
        return $tpl->parse(false, 'promo-wrap');
    }

    function parsePromoSign()
    {
        if (!isset($this->list->row['promos']) || \mb_strlen($this->list->row['promos']) < 3) {
            return '';
        }

        $tpl = $this->list->tpl();
        if (!isset($tpl->FILELIST['promo-sign'])) {
            $tpl->define(array(
                'promo-sign' => '/product/list/promo/sign.tpl',
            ));
        }
        return $tpl->parse(false, 'promo-sign');
    }
}
