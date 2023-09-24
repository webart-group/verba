<?php
namespace Verba\Mod\Acp\Router;

use Verba\Branch;
use Verba\Mod\Acp\Block\Product\MakeList;
use Verba\Mod\Acp\Block\Product\ProductAcpForm;
use Verba\Mod\Acp\Block\Product\Unlink;
use Verba\Mod\Acp\Router\Contracts\CatalogActionConfigInterface;
use Verba\Request;
use Verba\Request\Http\Router;
use function Verba\_mod;
use function Verba\_oh;

class Product extends Router
{
    function route()
    {
        $cfg_basement = 'acp/products/product';
        switch ($this->request->uf[0]) {
            case 'variant':
                $rq = clone $this->request;
                $router = new Product\ProductVariantAcpRouter($rq->shift());
                break;
            case 'unlink':
                $rq = clone $this->request;
                $router = new Unlink($rq->shift());
                break;
            case 'all':
                $rq = clone $this->request;
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
                    $router = new MakeList($rq);
                }
                break;
        }

        switch ($this->request->action) {
            case 'createform':
            case 'updateform':
                $routerClass = ProductAcpForm::class;
                break;

            case 'list':
                $routerClass = MakeList::class;
                break;
        }

        if (!isset($router) && isset($routerClass)) {
            $router = new $routerClass($this);
        }

        if (!isset($router)) {
            $h = (new ObjectType($this->request))->route();
        } else {

            if ($router instanceof CatalogActionConfigInterface) {
                $router->applyCatalogActionConfig();
            }

            $h = $router->route();
        }

        return $h;
    }
}
