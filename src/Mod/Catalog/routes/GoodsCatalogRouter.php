<?php

namespace Verba\Mod\Catalog\routes;

use Verba\Block\Json;
use Verba\Exception\Routing;
use Verba\Exception\Routing as RoutingException;
use Verba\Mod\Catalog\Block\Description;
use Verba\Mod\Catalog\Block\GoodsIndex;
use Verba\Mod\Catalog\helpers\PromoBlockPlaceHolder;
use Verba\Mod\Product\Block\ProductsList;
use Verba\Response\Html as HtmlResponse;
use Verba\Url;
use function Verba\_mod;
use function Verba\_oh;

class GoodsCatalogRouter extends Json
{
    public $catsData;
    public $currentCat;

    function route()
    {
        $mCat = \Verba\_mod('catalog');

        $catsData = $mCat->getCatsChain($this->request->uf, 0);

        if (!$catsData) {
            throw new RoutingException();
        }

        if (count($this->request->uf) > 1 && end($this->request->uf) == '') {
            $uf = $this->request->uf;
            array_pop($uf);
            $relocateUrl = new Url('/' . implode('/', $uf));
            $h = new HtmlResponse($this);
            $h->addHeader('HTTP/1.1 301 Moved Permanently');
            $h->addHeader('Location: ' . $relocateUrl->get(true));
            return $h->route();
        }

        $currentCat = end($catsData);

        $this->request->addParam([
            'catsData' => $catsData
        ]);

        $childChain = $mCat->getItemsByParent($currentCat['id']);
        $this->request->addParam(array(
            'childChain' => $childChain
        ));

        $mCat->addCatsToBreadcrumbs($catsData);

        if (is_array($childChain)) {
            $handler = (new GoodsIndex($this))->route();
        } else {
            $handler = $this;
        }

        if (!isset($handler)) {
            throw new RoutingException();
        }

        return $handler;
    }

    function prepare()
    {
        $_catalog = _oh('catalog');

        $mCat = _mod('catalog');
        $this->catsData = $this->request->getParam('catsData');

        if (!$this->catsData) {
            $this->catsData = $mCat->getCatsChain($this->request->uf, 0);
        }

        if (!$this->catsData) {
            throw new Routing();
        }

        $this->currentCat = end($this->catsData);
        if (!$this->currentCat['active']) {
            throw new Routing();
        }

        $this->request->addParam(array(
            'pot' => $_catalog->getID(),
            'piid' => $this->currentCat[$_catalog->getPAC()],
            'cfg' => 'public products'
        ));
        if (is_string($this->currentCat['config']) && !empty($this->currentCat['config'])
            && is_array($ccfg = unserialize($this->currentCat['config']))
            && isset($ccfg['filters'])
            && is_array($ccfg['filters'])
            && !empty($ccfg['filters'])
        ) {
            $this->request->addParam(array(
                'dcfg' => array(
                    'filters' => array(
                        'items' => $ccfg['filters']
                    )
                )
            ));
        }

        $this->addItems(array(
            'CATALOG_PRODUCTS' => new ProductsList($this),
        ));

        if (!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])) {
            $this->addItems(array(
                'CATALOG_DESCRIPTION' => new PromoBlockPlaceHolder($this, array(
                    'items' => array(new Description($this))
                )),
            ));
        }
    }

    function build()
    {
        $this->content = [
            'code' => $this->currentCat['code'],
            'title' => isset($this->currentCat['exttitle']) && !empty($this->currentCat['exttitle']) ? $this->currentCat['exttitle'] : $this->currentCat['title'],
            'products_list' => $this->items['CATALOG_PRODUCTS']->getContent()
        ];

//        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
//            $url = new Url($_SERVER['SCRIPT_URL']);
//            $this->addHeadTag('link', array('rel' => 'canonical', 'href' => $url->get(true)));
//        }
        return $this->content;
    }
}
