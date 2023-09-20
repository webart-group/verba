<?php

namespace webart\verba\src\Mod\Offer\Block;

use chatik_pageInstance;
use Exception;
use game_pageMenu;
use ObjectType\Attribute\Handler;
use page_coloredPanel;
use page_contentTitled;
use shop_ibFormRouter;
use store_infoAndAnnounces;
use store_reviewsAndForm;
use Verba\Block\Json;
use Verba\Exception\Routing;
use Verba\Hive;
use Verba\Lang;
use Verba\Mod\Store;
use Verba\Model;
use Verba\Model\Item;
use function Verba\_oh;

class OfferJson extends Json
{

    /**
     * @var Item
     */
    public $prodItem;

    public $prodItemId;

    /**
     * @var Model
     */
    public $_prod;

    /**
     * @var \Verba\Model\Store
     */
    public $Store;

    public $reviewsBlockCfg;

    function init()
    {

        if (!$this->prodItem) {
            $this->prodItem = _oh($this->rq->ot_id)->initItem($this->rq->iid);
        }

        if (!$this->prodItem || !$this->prodItem->getId()) {
            throw new Routing('Unknown Product');
        }

        $this->_prod = _oh($this->prodItem->getOh());

        if (!$this->Store instanceof \Verba\Model\Store) {
            $this->Store = new \Verba\Model\Store($this->prodItem->getNatural('storeId'), 'store');
        }

        if (!$this->Store->id) {
            throw new Exception('Unknown Prod Store');
        }

        return true;
    }

    function route()
    {
        $mStore = Store::getInstance();

        $this->addItems(array(

            'PANEL_STORE_INFO' => new store_infoAndAnnounces($this, array('Store' => $this->Store)),

            'PANEL_STORE_REVIEWS' => new page_coloredPanel($this, array(
                'items' => array(
                    'CONTENT' => new store_reviewsAndForm($this, $this->reviewsBlockCfg),
                ),
                'title' => Lang::get('store reviews panelTitle'),
                'scheme' => 'brown',
            )),

            'PANEL_STORE_CHAT' => new page_coloredPanel($this, array(
                    'items' => array('CONTENT' => new chatik_pageInstance($this, array(
                        'channel' => $mStore->genChatChannelToUser($this->Store),
                        'notifierCfg' => 'user'
                    ))),
                    'title' => Lang::get('profile orders chat panelTitle'),
                    'scheme' => 'blue',
                )
            ),

        ));

        $routed = new page_contentTitled($this->rq, array(
            'items' => array(
                'CONTENT' => $this,
                'TITLE' => new game_pageMenu($this->rq, array(
                    'gsr' => $this->gsr,
                ))
            ),
            'templates' => array(
                'title' => false,
            )
        ));
        return $routed->route();
    }

    function build()
    {

        $this->tpl->assign(['PANEL_ORDER' => $this->parseOrderPanel()]);

        $this->content = $this->tpl->parse(false, 'content');
        Hive::setBackURL();

        return $this->content;
    }

    function parseOrderPanel()
    {
        $this->tpl->define([
            'offer-info-and-form' => '/game/offer/offer_info_and_ibform.tpl',
            'info-wrap' => '/game/offer/info_wrap.tpl',
            'info-row' => '/game/offer/info_row.tpl'
        ]);

        // InfoFields
        $this->tpl->assign([
            'INFO_ROWS' => '',
            'OFFER_INFO_FIELDS' => '',
        ]);
        $fields = isset($this->gsr->service->config['groups']['offer_fields']['items'])
            ? $this->gsr->service->config['groups']['offer_fields']['items']
            : false;

        if ($fields && is_array($fields) && !empty($fields)) {
            foreach ($fields as $i => $attr_data) {
                if (!$this->_prod->isA($attr_data['code'])) {
                    continue;
                }
                $A = $this->_prod->A($attr_data['code']);
                if ($attr_data['handler']) {
                    $handler = Handler::extractHandlerFromCfg($this->_prod, $attr_data['code'], $attr_data['handler']);
                    $handler->setValue($this->prodItem->getValue($A->getCode()));
                } else {
                    $handler = false;
                }

                $this->tpl->assign(array(
                    'FIELD_CODE' => $A->getCode(),
                    'FIELD_TITLE' => $A->getTitle(),
                    'FIELD_CONTENT' => is_object($handler)
                        ? $handler->run()
                        : $this->prodItem->getValue($A->getCode()),
                ));
                $this->tpl->parse('INFO_ROWS', 'info-row', true);
            }
            $this->tpl->parse('OFFER_INFO_FIELDS', 'info-wrap');
        }

        // instabuy form
        $ibform = (new shop_ibFormRouter($this, array(
            '_prod' => $this->_prod,
            'prodItem' => $this->prodItem,
            'service' => $this->gsr->service,
            'Store' => $this->Store,
        )))->route();
        $ibform->prepare();
        $ibform->build();
        $this->tpl->assign('IBFORM', $ibform->content);
        $this->mergeHtmlIncludes($ibform);

        $panel = new page_coloredPanel($this, array(
            'title' => Lang::get('trq formTitle'),
            'content' => $this->tpl->parse(false, 'offer-info-and-form'),
            'scheme' => 'green',
        ));

        $panel->prepare();
        $panel->build();

        return $panel->content;
    }

}