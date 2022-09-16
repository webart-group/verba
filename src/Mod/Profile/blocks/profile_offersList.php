<?php

class profile_offersList extends \Verba\Block\Json
{

    use game_offers;

    function build()
    {

        $this->content = $this->owd->game->id . '-' . $this->owd->service->id . '-' . $this->owd->Store->id;
        $_cat = \Verba\_oh('catalog');
        $catCfg = $this->owd->service->config;

        $itemsOtId = $this->owd->service->itemsOtId;

        if (!$itemsOtId || !\Verba\isOt($itemsOtId) || !($oh = \Verba\_oh($itemsOtId))) {
            throw  new \Verba\Exception\Building('Bad prod type');
        }

        if ($oh instanceof \Model\Product\Resource) {
            $cfg_by_prodType = ' public/profile/storebids/prod_resource';
        }

        $cfgNames = 'public public/profile/storebids' . $cfg_by_prodType;

        $dcfg = array();

        \Verba\_mod('lister')->extendCfgByUiConfigurator($oh, $dcfg, $catCfg['groups']['store_fields'], $catCfg['groups']['store_filters']);

        $dcfg['url']['new'] = '/sell/' . $this->owd->game->code . '/' . $this->owd->service->code;

        /**
         * @var $Currency Currency
         */
        $Currency = \Verba\_mod('Currency');
        $storeCur = $Currency->getCurrency($this->owd->Store->currencyId);

        // добавление в название колонки 'цена' признак валюты магазина
        if (isset($dcfg['fields']['price'])
            && $storeCur
            && !isset($dcfg['fields']['price']['header']['textHandler'])) {
            if (!isset($dcfg['headers']['fields']['price'])) {
                $dcfg['headers']['fields']['price'] = array();
            }
            if ($storeCur) {
                $dcfg['headers']['fields']['price']['title'] = $oh->A('price')->getTitle() . ', ' . $storeCur->symbol;
            }
        }

        if (isset($dcfg['fields']['picture'])) {
            $dcfg['fields']['picture']['handler'] = array('\\Mod\\Offer\\Act\\MakeList\\Handler\\Field\\Image', null, 'offer');
        }

        // Если это ресурс, добавление редактирование на поле quantityAvaible и amountMin
//    foreach(array('quantityAvaible', 'amountMin') as $fname){
//      if(!isset($dcfg['fields'][$fname])) {
//        continue;
//      }
//      if(!isset($dcfg['headers']['fields']['price'])){
//        $dcfg['headers']['fields']['price'] = array();
//      }
//      $Currency = \Verba\_mod('Currency');
//      $storeCur = $Currency->getCurrency($this->owd->Store->currencyId);
//      if($storeCur){
//        $dcfg['headers']['fields']['price']['title'] = $oh->A('price')->getTitle().', '.$storeCur['symbol'];
//      }
//    }


        $cfg = array(
            'cfg' => $cfgNames,
            'dcfg' => $dcfg,
            'block' => $this,
        );

        $list = $oh->initList($cfg);
        $list->addExtData('cartCurrency', $storeCur);

        $list->addParents($_cat->getId(), $this->owd->service->id);
        $list->addExtendedData(array(
            'gameService' => $this->owd->service,
            'gameCat' => $this->owd->game,
        ));

        $qm = $list->QM();
        $qm->addWhere($this->owd->Store->id, 'storeId');

        $this->content = $list->generateList();
        $q = $list->QM()->getQuery();

        return $this->content;
    }
}

