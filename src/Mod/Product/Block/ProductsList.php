<?php

namespace Verba\Mod\Product\Block;

use Verba\Mod\Cart;
use Throwable;
use Verba\Act\MakeList;
use Verba\Block\Json;
use Verba\Exception\Building;
use Verba\Mod\Lister;
use Verba\Mod\Offer\Act\MakeList\Handler\Field\Image;
use Verba\Mod\Offer\Act\MakeList\Handler\Field\StoreInfo;
use Verba\Mod\Store;
use Verba\Mod\Store\Act\MakeList\Handler\Field\PriceFormater;
use function Verba\_mod;
use function Verba\_oh;
use function Verba\isOt;

class ProductsList extends Json
{
    public MakeList $list;

    function build()
    {
        try{

            $_catalog = _oh('catalog');
            $catData = $_catalog->getData($this->request->getParam('piid'), 1);
            if(!$catData) {
                throw new Building('Catalog missed or not found');
            }

            $listId = 'c' . $_catalog->getID() . '-' . $catData['id'];
            $catCfg = is_string($catData['config']) && !empty($catData['config'])
                ? unserialize($catData['config'])
                : [];

            $itemsOtId = $catData['itemsOtId'];
            if (!$_product = _oh($itemsOtId)) {
                throw  new Building('Bad prod type');
            }

            $cfgNames = ['public'];

            $dcfg = [
                'fields' => [],
                'order' => [
                    'priority' => []
                ],
            ];

            $Cart = Cart::getInstance();
            $userCurrency = $Cart->getCurrency();
            /**
             * @var $mLister Lister
             */
            $mLister = _mod('lister');
            $mLister->extendCfgByUiConfigurator($_product, $dcfg, $catCfg['groups']['public_fields'], $catCfg['groups']['public_filters']);

//            $dcfg['url']['forward'] = '/buy/list/' . $this->gsr->game->code . '/' . $this->gsr->service->code;
//            $dcfg['url']['new'] = '/sell/' . $this->gsr->game->code . '/' . $this->gsr->service->code;

            // добавляем обработчик на поле price если оно есть.
            if (isset($dcfg['fields']['price'])) {
                $dcfg['fields']['price']['handler'] = [PriceFormater::class];
                if (!isset($dcfg['workers'])) {
                    $dcfg['workers'] = array();
                    $dcfg['workers']['PriceFormatter'] = array(
                        'attr' => 'price'
                    );
                }
            }
            // добавляем обработчик на поле storeId если оно есть.
            if (isset($dcfg['fields']['storeId'])) {
                $dcfg['fields']['storeId']['handler'] = [StoreInfo::class];
            }

            // добавление в название колонки 'цена' признак валюты магазина
            if (isset($dcfg['fields']['price'])
                && !isset($dcfg['fields']['price']['header']['textHandler'])) {
                if (!isset($dcfg['headers']['fields']['price'])) {
                    $dcfg['headers']['fields']['price'] = array();
                }

                $dcfg['headers']['fields']['price']['title'] = $_product->A('price')->getTitle() . ', ' . $userCurrency->symbol;

            }

            // добавляем обработчик на поле picture если оно есть.
            if (isset($dcfg['fields']['picture'])) {
                $dcfg['fields']['picture']['handler'] = [Image::class];
            }

            $cfgNames[] = 'public/catalog/by_cat/' . $catData['id'];

            $cfg = array(
                'listId' => $listId,
                'cfg' => implode(' ', $cfgNames),
                'dcfg' => $dcfg,
                'block' => $this,
            );

            $list = $_product->initList($cfg);

            $list->addExtendedData(array(
                'cartCurrency' => $userCurrency
            ));
            $qm = $list->QM();
            $qm->addWhere(1, 'active');

            list($tA) = $qm->createAlias();
            list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());
            list($ctalias, $ctable, $cdb) = $qm->createAlias($_catalog->vltT());
            list($lcA, $lcT, $lcD) = $qm->createAlias($_product->vltT($_catalog));
            //$qm->addGroupBy(['id']);
            //подключение таблицы связей каталог-продукты
            $qm->addCJoin(
                [
                    [
                        'a' => $lcA
                    ]
                ],
                [
                    [
                        'p' => [ 'a'=> $lcA, 'f' => 'p_ot_id'],
                        's' => $_catalog->getID(),
                    ],
                    [
                        'p' => ['a'=> $lcA, 'f' => 'p_iid'],
                        's' => $catData['id']
                    ],
                    [
                        'p' => ['a'=> $lcA, 'f' => 'ch_iid'],
                        's' => ['a' => $palias, 'f' => $_product->getPAC()],
                    ],
                ], false, null, 'RIGHT', 'obligatory'
            );

            // подключение таблицы коэфициентов валют магазинов
            $iCurId = $userCurrency->getId();
            /**
             * @var $Store Store
             */
            $Store = _mod('store');
            $minpc_field = 'pcmin_' . $iCurId;
            list($cpkA, $cpkT) = $qm->createAlias($Store->cpk_table, SYS_DATABASE);
            $qm->addSelectPastFrom('`' . $cpkA . '`.`' . $minpc_field . '` as `minPc`', null, null, true);
            $qm->addWhere('`' . $cpkA . '`.`' . $minpc_field . '` > 0');

            // исключаем внутренние кошельки
            //$internalPaysysIds = \Verba\_mod('payment')->getInternalPaysysIds();

            $qm->addCJoin([['a' => $cpkA]],
                [
                    [
                        'p' => ['a' => $cpkA, 'f' => 'storeId'],
                        's' => ['a' => $tA, 'f' => 'storeId'],
                    ],
                ]
            );

            ###  Данные по магазину
            $_store = _oh('store');
            // store table, extract store picture
            list($storeA) = $qm->createAlias($_store->vltT(), SYS_DATABASE);
            $qm->addSelectPastFrom('picture', $storeA, 'store_picture');
            $qm->addSelectPastFrom('last_activity', $storeA, 'store_last_activity');
            $qm->addSelectPastFrom('rating', $storeA, 'store_rating');
            $qm->addSelectPastFrom('reviews_count', $storeA, 'store_reviews_count');
            $qm->addSelectPastFrom('reviews_stars', $storeA, 'store_reviews_stars');

            $qm->addCJoin([['a' => $storeA]],
                [
                    [
                        'p' => ['a' => $storeA, 'f' => 'id'],
                        's' => ['a' => $tA, 'f' => 'storeId'],
                    ],
                ]
            );

            $this->content = $list->generateListJson();

            $q = $list->QM()->getQuery();

            return $this->content;

        } catch (Throwable $e) {
            throw $e;
        }
    }
}
