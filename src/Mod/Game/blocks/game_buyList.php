<?php

class game_buyList extends \Verba\Block\Html
{

    /**
     * @var \Mod\Game\ServiceRequest
     */
    public $gsr;

    function build()
    {
        $this->content = '';

        if (!is_object($this->gsr) || !$this->gsr->isValid()) {
            throw  new \Verba\Exception\Building();
        }

        $catCfg = $this->gsr->service->config;
        $itemsOtId = $this->gsr->service->itemsOtId;

        if (!$itemsOtId || !\Verba\isOt($itemsOtId) || !($_prod = \Verba\_oh($itemsOtId))) {
            throw  new \Verba\Exception\Building('Bad prod type');
        }

        $cfgNames = ['public', 'public/game/offers'];

        $dcfg = array(
            'fields' => array(),
            'order' => array('priority' => array()),
        );

        $Cart = \Mod\Cart::getInstance();
        $userCurrency = $Cart->getCurrency();
        /**
         * @var $mLister Lister
         */
        $mLister = \Verba\_mod('lister');
        $mLister->extendCfgByUiConfigurator($_prod, $dcfg, $catCfg['groups']['public_fields'], $catCfg['groups']['public_filters']);

        $dcfg['url']['forward'] = '/buy/list/' . $this->gsr->game->code . '/' . $this->gsr->service->code;
        $dcfg['url']['new'] = '/sell/' . $this->gsr->game->code . '/' . $this->gsr->service->code;

        // добавляем обработчик на поле price если оно есть.
        if (isset($dcfg['fields']['price'])) {
            $dcfg['fields']['price']['handler'] = array('\Mod\Store\Act\MakeList\Handler\Field\PriceFormater');
            if (!isset($dcfg['workers'])) {
                $dcfg['workers'] = array();
                $dcfg['workers']['PriceFormatter'] = array(
                    'attr' => 'price'
                );
            }
        }
        // добавляем обработчик на поле storeId если оно есть.
        if (isset($dcfg['fields']['storeId'])) {
            $dcfg['fields']['storeId']['handler'] = array('Mod\Offer\Act\MakeList\Handler\Field\StoreInfo');
        }

        // добавление в название колонки 'цена' признак валюты магазина
        if (isset($dcfg['fields']['price'])
            && !isset($dcfg['fields']['price']['header']['textHandler'])) {
            if (!isset($dcfg['headers']['fields']['price'])) {
                $dcfg['headers']['fields']['price'] = array();
            }

            $dcfg['headers']['fields']['price']['title'] = $_prod->A('price')->getTitle() . ', ' . $userCurrency->symbol;

        }

        // добавляем обработчик на поле picture если оно есть.
        if (isset($dcfg['fields']['picture'])) {
            $dcfg['fields']['picture']['handler'] = ['Mod\Offer\Act\MakeList\Handler\Field\Image'];
        }

        $cfgNames[] = 'public/game/by_cat/' . $this->gsr->service->getId();

        $cfg = array(
            'listId' => 'gs' . $this->gsr->service->getId() . '-' . $itemsOtId,
            'cfg' => implode(' ', $cfgNames),
            'dcfg' => $dcfg,
            'block' => $this,
        );

        $list = $_prod->initList($cfg);

        $list->addExtendedData(array(
            'gameService' => $this->gsr->service,
            'gameCat' => $this->gsr->game,
            'cartCurrency' => $userCurrency
        ));
        $qm = $list->QM();
        $qm->addWhere(1, 'active');
        $qm->addWhere($this->gsr->game->id, 'gameCatId');
        $qm->addWhere($this->gsr->service->id, 'serviceCatId');

        list($tA) = $qm->createAlias();

        // подключение таблицы коэфициентов валют магазинов

        $iCurId = $userCurrency->getId();
        /**
         * @var $Store Store
         */
        $Store = \Verba\_mod('store');
        $minpc_field = 'pcmin_' . $iCurId;
        list($cpkA, $cpkT) = $qm->createAlias($Store->cpk_table, SYS_DATABASE);
        $qm->addSelectPastFrom('`' . $cpkA . '`.`' . $minpc_field . '` as `minPc`', null, null, true);
        $qm->addWhere('`' . $cpkA . '`.`' . $minpc_field . '` > 0');

        // исключаем внутренние кошельки
        //$internalPaysysIds = \Verba\_mod('payment')->getInternalPaysysIds();

        $qm->addCJoin(array(array('a' => $cpkA)),
            array(
                array(
                    'p' => array('a' => $cpkA, 'f' => 'storeId'),
                    's' => array('a' => $tA, 'f' => 'storeId'),
                ),
            )
        );

        ###  Данные по магазину
        $_store = \Verba\_oh('store');
        // store table, extract store picture
        list($storeA) = $qm->createAlias($_store->vltT(), SYS_DATABASE);
        $qm->addSelectPastFrom('picture', $storeA, 'store_picture');
        $qm->addSelectPastFrom('last_activity', $storeA, 'store_last_activity');
        $qm->addSelectPastFrom('rating', $storeA, 'store_rating');
        $qm->addSelectPastFrom('reviews_count', $storeA, 'store_reviews_count');
        $qm->addSelectPastFrom('reviews_stars', $storeA, 'store_reviews_stars');

        $this->content = $list->generateList();

        $q = $list->QM()->getQuery();

        return $this->content;
    }

}
