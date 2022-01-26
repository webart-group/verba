<?php

namespace Verba\Act\MakeList\Handler\Row;

use \Verba\Act\MakeList\Handler\Row;

class ReviewRibbonPanel extends Row
{

    function run()
    {

        $tpl = $this->list->tpl();
        /**
         * @var $mReview \Mod\Review
         * @var $mShop \Mod\Shop
         * @var $mCart \Mod\Cart
         * @var $mCurrency \Mod\Currency
         */
        $mReview = \Verba\_mod('review');
        $mCurrency = \Verba\_mod('currency');
        $mShop = \Verba\_mod('shop');
        $mCart = \Verba\_mod('cart');
        $cartCur = $mCart->getCart()->getCurrency();

        $nominal = $mReview->getNominalFromRatingId($this->list->row['rating']);
        if (!$nominal) {
            $nominal = 0;
        }

        $this->list->rowClass[] = '_rw_rating_' . $nominal;

        $tpl->assign(array(
            'ITEM_RATING_TEXT' => $this->list->row['rating__value'],
            'ITEM_RATING' => $nominal,
            'ITEM_REVIEW' => $this->list->row['review'],
            'MASKED_AUTHOR' => $this->maskAuthor($this->list->row['owner__value']),
            'ITEM_CREATED_DATE' => $mShop->formatDate(strtotime($this->list->row['created'])),
        ));

        if ($this->list->row['prodPrice'] && $this->list->row['prodCurrencyId']) {

            $itemUrl = '/offer' . \Mod\Seo::idToSeoStr(array(
                    'ot_id' => $this->list->row['prodOt'],
                    'id' => $this->list->row['prodId'],
                    'url_code' => '',
                ));

            if ($this->list->row['prodCurrencyId'] != $cartCur->getId()) {
                $prodCost = $mShop->convertCur(
                    $this->list->row['prodPrice'],
                    $this->list->row['prodCurrencyId'],
                    $cartCur->getId()
                );
            } else {
                $prodCost = $this->list->row['prodPrice'];
            }


            $tpl->assign(array(
                'PROD_TITLE' => !empty($this->list->row['prodTitle']) ?
                    htmlspecialchars($this->list->row['prodTitle'])
                    : '',
                'PROD_URL' => $itemUrl,
                'PROD_COST' => $prodCost,
                'PROD_CURRENCY_UNIT' => htmlspecialchars($cartCur->p('symbol')),
            ));
        } else {
            $tpl->assign(array(
                'PROD_TITLE' => '',
                'PROD_COST' => '',
                'PROD_CURRENCY_UNIT' => '',
            ));
            $this->list->rowClass[] = '_rw_noprod';
        }


        return true;
    }

    function maskAuthor($val)
    {
        if (!is_string($val) || !mb_strlen($val)) {
            return '';
        }
        return mb_substr($val, 0, 1) . '*****';
    }

}
