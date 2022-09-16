<?php
namespace Verba\Mod\Acp\Router;

class Product extends \Verba\Request\Http\Router
{

    function route()
    {
        $isVariant = false;
        switch ($this->request->uf[0]) {
            case 'variant':
                $rq = clone $this->request;
                $rq->uf = array_slice($rq->uf, 1);
                $router = new Product\Variant($rq);
                $isVariant = true;
                break;
            case 'unlink':
                $rq = clone $this->request;
                $rq->uf = array_slice($rq->uf, 1);
                $router = new \Verba\Mod\Acp\Block\Product\Unlink($rq);
                break;
            case 'all':
                $rq = clone $this->request;
                $oh = \Verba\_oh('product');
                $rq->uf = array_slice($rq->uf, 1);
                if (count($rq->uf)) {
                    switch ($rq->uf[0]) {
                        case 'cuform':
                            $this->request->addParam(array(
                                'cfg' => 'acp/products/product'
                            ));
                    }
                } else {
                    $rq->addParams(array(
                        'cfg' => 'acp/products/product acp/products/allprods'
                    ));
                    $router = new \Verba\Mod\Acp\Block\Product\MakeList($rq);
                }
                break;
        }
        $cat_cfg = false;
        $_cat = \Verba\_oh('catalog');
        \Verba\_mod('catalog');
        $cat_ot_id = $_cat->getID();
        // if catalog specified, try to extract all-catalog-chain configs
        $catsChain = array();
        if (isset($this->request->pot[$cat_ot_id]) && !empty($this->request->pot[$cat_ot_id])) {
            $cat_iid = is_array($this->request->pot[$cat_ot_id])
                ? current($this->request->pot[$cat_ot_id])
                : $this->request->pot[$cat_ot_id];
            $br = \Verba\Branch::get_branch(array($_cat->getID() => array('iids' => $cat_iid, 'aot' => $_cat->getID())),
                'up', 10, true, true, false, false);
            $catsChain = \Verba\Branch::build_tree($br, 2);
            $catsChain = array_reverse($catsChain);
            $catsChain = $_cat->getData($catsChain, true);
            $catData = &$catsChain[$cat_iid];
            if ($catData['config'] && !empty($catData['config'])) {
                $cat_cfg = unserialize($catData['config']);
                if (isset($cat_cfg['ot'])) {
                    $oh = \Verba\_oh($cat_cfg['ot']);
                    $this->request->ot_id = $oh->getID();
                    $this->request->ot_code = $oh->getCode();
                }
            }
        }

        switch ($this->request->action) {
            case 'createform':
            case 'updateform':
                if (isset($oh)) {
                    $this->request->addParam(array(
                        'cfg' => 'acp/products/product acp/products/' . $oh->getCode()
                    ));
                }
                break;
            case 'list':
                $routerClass = '\Mod\Acp\Block\Product\MakeList';
                break;
        }
        if (!$this->request->getParam('cfg')) {
            if (($this->request->action == 'list' || $this->request->action == 'all')
                && !$isVariant) {
                $catsChainConfigs = '';
                if (is_array($catsChain) && count($catsChain)) {
                    $catsCfgRoot = '';
                    foreach ($catsChain as $cid => $cdata) {
                        $cfragment = empty($catsCfgRoot) ? $cdata['code'] : $catsCfgRoot . '-' . $cdata['code'];
                        $catsChainConfigs .= ' acp/cat/' . $cfragment;
                        $catsCfgRoot = $cfragment;
                    }
                }
                $this->request->addParam(array(
                    'cfg' => 'acp/products/product acp/products/' . $this->request->ot_code . ' ' . $catsChainConfigs
                ));
            }
        }

        if (!isset($router) && isset($routerClass)) {
            $router = new $routerClass($this);
        }

        if (!isset($router)) {
            $h = parent::route();
        } else {
            $h = $router->route();
        }

        return $h;
    }

}





