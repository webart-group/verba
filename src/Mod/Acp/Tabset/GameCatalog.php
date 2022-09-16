<?php

namespace Verba\Mod\Acp\Tabset;


class GameCatalog extends \Verba\Mod\Acp\Tabset
{
    function tabs()
    {
        $productOt = isset($this->node->itemData['itemsType']) && !empty($this->node->itemData['itemsType'])
            ? $this->node->itemData['itemsType']
            : false;
        $productsTab = array();
        $extraTabs = array();
        if ($productOt) {
            $productsTab['GameProductsList'] = array('ot' => $productOt);
            $_prod = \Verba\_oh($productOt);
            if ($_prod instanceof \Model\Product\Resource) {
                $_cat = \Verba\_oh('catalog');
                $_game = \Verba\_oh('game');
                $vltUri = \Verba\_oh('game')->vltURI($_cat);
                $q = "SELECT `p_iid` as `gameId` FROM " . $vltUri . " 
        WHERE 
        p_ot_id ='" . $_game->getID() . "' 
        && `ch_ot_id` = '" . $_cat->getID() . "'
        && `ch_iid` = '" . $this->node->id . "' 
        LIMIT 1";

                if (false !== ($sqlr = $this->DB()->query($q))
                    && $sqlr->getNumRows()
                    && is_array($row = $sqlr->fetchRow())
                    && is_numeric($row['gameId'])) {
                    $extraTabs['LinkedGameServers'] = array(
                        'pot' => array(
                            $_cat->getID() => array($this->node->id => $this->node->id),
                            $_game->getID() => array($row['gameId'] => $row['gameId']),
                        ),
                        'linkedTo' => array('type' => 'tab', 'id' => 'CatalogAef')
                    );
                }
            }
        }

        $commonTabs = array(
            'CatalogAef',
            'CatalogGoodsProps' => array(
                'button' => array(
                    'title' => 'catalog acp tab goodsProps'
                ),
                'ot' => 'catalog',
                'url' => '/acp/h/catalog/cuform-configs',
                'instanceOf' => array('type' => 'node'),
            ),
            'CatalogGoodsTradeForm' => array(
                'button' => array(
                    'title' => 'catalog acp tab tradeForm'
                ),
                'ot' => 'catalog',
                'url' => '/acp/h/catalog/cuform/tform',
                'instanceOf' => array('type' => 'node'),
            ),

            'MetaAef' => array('linkedTo' => array('id' => 'CatalogAef'))
        );

        return array_merge($productsTab, $commonTabs, $extraTabs);
    }
}
