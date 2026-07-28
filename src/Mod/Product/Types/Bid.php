<?php
namespace Verba\Mod\Product\Types;


class Bid extends \Verba\Mod\Product\Types\Product {

    function extractTitle(){

        $title = parent::extractTitle();
        if(is_string($title) && !empty($title)){
            return $title;
        }
        $title = $this->oh->getTitle();

        $this->row['title'] = $title;
        return $this->row['title'];
    }

    function extractCustoms()
    {
        parent::extractCustoms();
        $this->extractDescription();
    }

    public function extractDescription()
    {

        $item_hash = current($this->iidsHashes[$this->row[$this->oh->getPAC()]]);
        $cartItemData = $this->items2prepare[$item_hash];

        $Item = $this->oh->initItem($this->row);


        $desc = array();

        $catalogItem = \Verba\_mod('catalog')->OTIC()->getItem($this->row['p_iid']);
        $catCfg = $catalogItem->getValue('config');

        if(!empty($catCfg['groups']['order_description']['items'])) {
            foreach ($catCfg['groups']['order_description']['items'] as $fi => $fiData){
                $desc[] = $Item->getValue($fiData['code']);
            }
        }

        // возможными полями из tfrom
        if(is_array($cartItemData) && $cartItemData['_tform']
            && $cartItemData['_tform']['ot_id']
            && $cartItemData['_tform']['id']
            && $cartItemData['_tform']['data']
            && isset($catCfg)
            && is_array($catCfg)
            && isset($catCfg['groups']['tform']['items'])
            && is_array($catCfg['groups']['tform']['items'])
        )
        {
            $tformItem = \Verba\_oh($cartItemData['_tform']['ot_id'])->initItem($cartItemData['_tform']['data']);
            foreach ($catCfg['groups']['tform']['items'] as $fi => $fiData){
                if(!isset($fiData['orderDescription']) || !$fiData['orderDescription']){
                    continue;
                }
                $desc[] = $tformItem->getValue($fiData['code']);
            }
        }

        $this->row['description'] = implode(', ',$desc);

        return $this->row['description'];
    }
}
