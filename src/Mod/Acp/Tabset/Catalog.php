<?php

namespace Verba\Mod\Acp\Tabset;


use Verba\Mod\Acp\Tab\Form\CatalogAef;
use Verba\Mod\Acp\Tab\Form\MetaAef;
use Verba\Model\Product;
use function Verba\_oh;

class Catalog extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        $productOt = isset($this->node->itemData['itemsType']) && !empty($this->node->itemData['itemsType'])
            ? $this->node->itemData['itemsType']
            : false;
        $productsTab = [];
        $extraTabs = [];
        if ($productOt) {
            $productsTab['ProductsList'] = ['ot' => $productOt];
            $_prod = _oh($productOt);
        }

        $commonTabs = [
            'CatalogAef',
            'CatalogGoodsProps' => [
                'button' => [
                    'title' => 'catalog acp tab goodsProps'
                ],
                'ot' => 'catalog',
                'url' => '/acp/h/catalog/cuform-configs',
                'instanceOf' => [
                    'type' => 'node'
                ],
            ],
            'CatalogGoodsTradeForm' => [
                'button' => [
                    'title' => 'catalog acp tab tradeForm'
                ],
                'ot' => 'catalog',
                'url' => '/acp/h/catalog/cuform/tform',
                'instanceOf' => [
                    'type' => 'node'
                ],
            ],
            'MetaAef' => [
                'linkedTo' => [
                    'id' => CatalogAef::class
                ]
            ]
        ];

        return array_merge($productsTab, $commonTabs, $extraTabs);
    }
}
