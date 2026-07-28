<?php

namespace Verba\Mod\Product\Act\MakeList\Handler\Row;

use Verba\Lang;
use Verba\Mod\Product\Act\MakeList\Handler\ProductHandlerFieldTrait;

class RowDefaultHandler extends \Verba\Act\MakeList\Handler\Row
{
    use ProductHandlerFieldTrait;

    static $c = 0;

    public ?array $_promos = null;
    public $_discountByGoods = 0;

    function dPrice($val) {
        if(!$this->_discountByGoods) {
            return $val;
        }

        $val = (float)$val;
        $p = $val / 100;
        $r = number_format($val - ($p * $this->_discountByGoods),2);

        return \Verba\reductionToCurrency($r);
    }

    function run()
    {
        self::$c++;
        $tpl = $this->list->tpl();
        $oh = \Verba\_oh($this->list->row['ot_id']);
        $url = \Verba\Mod\Seo::idToSeoStr($this->list->row, array('seq' => $this->list->getCurrentPos(), 'slID' => $this->list->getID()));
        $iid = $this->list->row[$oh->getPAC()];
        $cart = \Verba\_mod('cart');
        $cur = $cart->getCurrency();

        $this->getPromos();

        $variants = [
            $iid => [
                'size' => $this->list->row['size'],
                'dprice' => \Verba\reductionToCurrency($this->dPrice($this->list->row['price']) * $cur->rate),
                'price' => \Verba\reductionToCurrency($this->list->row['price'] * $cur->rate),
                'size_unit' => $this->list->row['size_unit'],
                'size_unit__value' => $this->list->row['size_unit__value'],
                'in_stock' => $this->list->row['in_stock'],
                'color' => $this->list->row['color'],
                'articul' => $this->list->row['articul'],
                'barcode' => $this->list->row['barcode'],
            ],
        ];

        if (!empty($this->list->row['variant'])) {
            $cv = explode('#', $this->list->row['variant']);
            foreach ($cv as $v) {
                $v = explode(':', $v);
                $variants[$v[0]] = array(
                    'dprice' => \Verba\reductionToCurrency($this->dPrice($v[1]) * $cur->rate),
                    'price' => \Verba\reductionToCurrency($v[1] * $cur->rate),
                    'size' => $v[2],
                    'size_unit' => $v[3],
                    'size_unit_pdv' => (string)$this->getSizeUnitPdValue($v[3]),
                    'in_stock' => $v[4],
                    'color' => $v[5],
                    'articul' => $v[6],
                    'barcode' => $v[7],
                );
            }
            uasort($variants, array(\Verba\_mod('product'), 'sortVariants'));
        } else {
            $this->list->rowClass[] = 'one-variant';
        }

        if (!isset($tpl->FILELIST['variant-wrap'])) {
            $tpl->define(array(
                'variant-wrap' => '/product/list/variant/wrap.tpl',
                'variant-item' => '/product/list/variant/item.tpl',
                'old_price' => '/product/listCard/old_price.tpl',
            ));
        }

        $tpl->assign(array(
            'ITEM_CURRENCY_SHORT' => $cur->short,
            //'ITEM_PROMOS' => $this->parsePromos(),
            'ITEM_PROMOTION' => $this->parsePromo(),
            'ITEM_PROMO_SIGN' => $this->parsePromoSign(),
        ));

        $tpl->clear_vars(array('VARIANTS_ITEMS'));
        $tpl->assign(array(
            'VARIANT_OT_ID' => $this->list->row['ot_id'],
        ));

        foreach($variants as $vid => $cvar) {
            $in_stock = isset($cvar['in_stock']) && $cvar['in_stock'] == 1;

            if (!empty($cvar['color'])) {
                $color = htmlspecialchars($cvar['color']);
                $color_sign = '';
            } else {
                $color = '';
                $color_sign = ' no-color';
            }

            if($cvar['dprice'] && $cvar['dprice'] != $cvar['price']){
                $this->list->rowClass[] = 'discount';
                $tpl->assign(array(
                    'ITEM_OLD_PRICE' => $cvar['price'],
                ));
                $actual_price = $cvar['dprice'];
                $tpl->parse('OLD_PRICE_E', 'old_price');
            }else{
                $tpl->assign('OLD_PRICE_E', '');
                $actual_price = $cvar['price'];
            }

            $tpl->assign(array(
                'VARIANT_ID' => $vid,
                'VARIANT_SIZE' => $cvar['size_unit'] == 1132 ? (string)$cvar['size'] : \Verba\reductionToFloat($cvar['size']),
                'VARIANT_SIZE_UNIT' => (string)$cvar['size_unit__value'],
                'VARIANT_PRICE' =>\Verba\reductionToCurrency($actual_price),
                'VARIANT_COLOR' => $color,
                'VARIANT_COLOR_SIGN' => $color_sign,
                'VARIANT_IN_STOCK_SIGN' => $in_stock ? 'in-stock' : 'not-in-stock',
                'VARIANT_IN_STOCK' => (int)$in_stock,
                'VARIANT_IN_STOCK_TITLE' => Lang::get('products fields in_stock '.((int)$in_stock)),
                'VARIANT_ARTICUL' => $cvar['articul'],
                'VARIANT_BARCODE' => $cvar['barcode'] ?? '—',
            ));


            $tpl->parse('VARIANTS_ITEMS', 'variant-item', true);
        }

        $tpl->parse('ITEM_VARIANTS', 'variant-wrap');

        $cc = (int)$this->list->row['reviews_count'];
        if (!$cc) {
            $cw = '';
        } else {
            $cw = Lang::make_padej(
                $cc,
                Lang::get('review count forms root'),
                Lang::get('review count forms cases')
            );
        }
        reset($variants);
        $first_var = current($variants);

        if($first_var['dprice'] && $first_var['dprice'] != $first_var['price']){
            $dpriced = true;
            $actual_price = $first_var['dprice'];
            $old_price = $first_var['price'];
            $tpl->assign(array(
                'ITEM_OLD_PRICE' => $old_price,
            ));
            $tpl->parse('OLD_PRICE_E', 'old_price');
        }else{
            $dpriced = false;
            $tpl->assign('OLD_PRICE_E', '');
            $actual_price = $first_var['price'];
            $old_price = $first_var['price'];
        }


        $tpl->assign(array(
            'ITEM_REVIEWS_COUNT_VAL' => $cc,
            'ITEM_REVIEWS_WORD' => $cw,
            'ITEM_ARTICUL' => $this->list->row['articul'],
            'ITEM_BRANDID_VALUE' => $this->list->row['brandId__value'],
            'ITEM_PAGE_URL' => $url,
            'ITEM_BASE_PRICE' => $actual_price > 0 ? $actual_price : '',
            '_ITEM_PRICE' => $actual_price > 0 ? \Verba\reductionToCurrency($actual_price * $cur->rate) : '',
            'ITEM_OT' => $this->list->row['ot_id'],
            'ITEM_ID' => $iid,
            '_ITEM_SIZE' => \Verba\reductionToFloat($first_var['size']),
            '_ITEM_SIZE_UNIT_TITLE' => $first_var['size_unit__value'],
            'ITEM_OLD_PRICE' => '',
            'ITEM_PRICE_DISCOUNTED_SIGN' => $dpriced ? ' discounted' : '',
        ));

        return null;
    }

    function getPromos() {
        if($this->_promos === null){
            $this->_promos = $this->extractPromos();
        }
        return $this->_promos;
    }

    function extractPromos(){
        $Promos = \Verba\_mod('promotion');
        $promos = [];
        if(!isset($this->list->row['promos']) || mb_strlen($this->list->row['promos']) < 3){
            return $promos;
        }
        foreach(explode('~', $this->list->row['promos']) as $promoasstr){
            list($id, $title, $anno, $dcontext, $daffect, $dcfg) = explode('^', $promoasstr);
            $params = $Promos->decodeDCfg($dcfg);
            $promos[$id] = array(
                'title' => $title,
                'annotation' => $anno,
                'context' => $dcontext,
                'affect' => $daffect,
                'params' => $params,
            );

            if($daffect == 'goods'
                && isset($params['value'])
                && is_numeric($params['value'])
            ) {
                $dv = (float)$params['value'];
                $this->_discountByGoods += $dv;
            }
        }

        return $promos;
    }

    function parsePromo()
    {
        if (!isset($this->list->row['promos']) || \mb_strlen($this->list->row['promos']) < 3) {
            return '';
        }
        $tpl = $this->list->tpl();
        if (!isset($tpl->FILELIST['promo-wrap'])) {
            $tpl->define(array(
                'promo-wrap' => '/product/listCard/promotion.tpl',
            ));
        }
        $tpl->clear_vars(['PROMOTION_TITLE']);

        $promos = explode('~', $this->list->row['promos']);
        foreach ($promos as $promoasstr) {
            list($id, $title, $anno) = explode('^', $promoasstr);
            $tpl->assign(array(
                'PROMOTION_TITLE' => !empty($anno) ? $anno : $title
            ));
            break;
        }
        return $tpl->parse(false, 'promo-wrap');
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
//
//        $tpl = $this->list->tpl();
//        if (!isset($tpl->FILELIST['promo-sign'])) {
//            $tpl->define(array(
//                'promo-sign' => '/product/list/promo/sign.tpl',
//            ));
//        }
//        return $tpl->parse(false, 'promo-sign');
        $this->list->rowClass[] = 'onsale';
        return 'onsale';
    }
}
