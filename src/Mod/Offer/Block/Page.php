<?php

namespace Verba\Mod\Offer\Block;

class Page extends \Verba\Block\Html
{

    public $css = array('offer');

    public $templates = array(
        'content' => 'game/offer/wrap.tpl',
    );

    public $tplvars = array(
        'PANEL_STORE_INFO' => '',
        'PANEL_STORE_REVIEWS' => '',
    );

    /**
     * @var \Verba\Mod\Game\ServiceRequest
     */
    public $gsr;

    /**
     * @var \Model\Item
     */
    public $prodItem;

    public $prodItemId;

    /**
     * @var \Model
     */
    public $_prod;

    /**
     * @var \Model\Store
     */
    public $Store;

    public $reviewsBlockCfg;

    function init()
    {

        if (!$this->prodItem) {
            $this->prodItem = \Verba\_oh($this->rq->ot_id)->initItem($this->rq->iid);
        }

        if (!$this->prodItem || !$this->prodItem->getId()) {
            throw new \Verba\Exception\Routing('Unknown Product Item');
        }

        $this->_prod = \Verba\_oh($this->prodItem->getOh());

        if (!$this->Store instanceof \Model\Store) {
            $this->Store = new \Model\Store($this->prodItem->getNatural('storeId'), 'store');
        }
        if (!$this->Store->id) {
            throw new \Exception('Unknown Prod Store');
        }

        return true;
    }

    function route()
    {

        $mStore = \Verba\Mod\Store::getInstance();

        $this->addItems(array(

            'PANEL_STORE_INFO' => new \store_infoAndAnnounces($this, array('Store' => $this->Store)),

            'PANEL_STORE_REVIEWS' => new \page_coloredPanel($this, array(
                'items' => array(
                    'CONTENT' => new \store_reviewsAndForm($this, $this->reviewsBlockCfg),
                ),
                'title' => \Verba\Lang::get('store reviews panelTitle'),
                'scheme' => 'brown',
            )),

            'PANEL_STORE_CHAT' => new \page_coloredPanel($this, array(
                    'items' => array('CONTENT' => new \chatik_pageInstance($this, array(
                        'channel' => $mStore->genChatChannelToUser($this->Store),
                        'notifierCfg' => 'user'
                    ))),
                    'title' => \Verba\Lang::get('profile orders chat panelTitle'),
                    'scheme' => 'blue',
                )
            ),

        ));

        $routed = new \page_contentTitled($this->rq, array(
            'items' => array(
                'CONTENT' => $this,
                'TITLE' => new \game_pageMenu($this->rq, array(
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
        \Verba\Hive::setBackURL();

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
                    $handler = \ObjectType\Attribute\Handler::extractHandlerFromCfg($this->_prod, $attr_data['code'], $attr_data['handler']);
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
        $ibform = (new \shop_ibFormRouter($this, array(
            '_prod' => $this->_prod,
            'prodItem' => $this->prodItem,
            'service' => $this->gsr->service,
            'Store' => $this->Store,
        )))->route();
        $ibform->prepare();
        $ibform->build();
        $this->tpl->assign('IBFORM', $ibform->content);
        $this->mergeHtmlIncludes($ibform);

        $panel = new \page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('trq formTitle'),
            'content' => $this->tpl->parse(false, 'offer-info-and-form'),
            'scheme' => 'green',
        ));

        $panel->prepare();
        $panel->build();

        return $panel->content;
    }

}