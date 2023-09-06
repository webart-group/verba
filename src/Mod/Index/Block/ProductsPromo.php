<?php

namespace Verba\Mod\Index\Block;

use Verba\Block\Json;
use Verba\Lang;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class ProductsPromo extends Json
{
    function build()
    {
        $_prod = _oh('product_wood');

        $list = $_prod->initList([
            'cfg' => 'public/index/products-promo'
        ]);
        $qm = $list->QM();
        $q = $qm->getQuery();
        return $this->content = $list->generateListJson();
    }
}
