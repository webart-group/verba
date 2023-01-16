<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 19:26
 */

namespace Verba\Mod\Acp\Router\Product;


use Verba\Mod\Acp\Router\Contracts\CatalogActionConfigInterface;
use Verba\Mod\Acp\Router\Contracts\CatalogActionConfigTrait;
use Verba\Request\Http\Router;

class ProductVariantAcpRouter extends Router implements CatalogActionConfigInterface
{
    use CatalogActionConfigTrait;

    function getCatalogActionConfig()
    {
        return $this->_catalogActionDefaultConfig . ' acp/products/product-var';
    }
}
