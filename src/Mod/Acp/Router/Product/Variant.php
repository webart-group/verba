<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 19:26
 */

namespace Verba\Mod\Acp\Router\Product;


class Variant extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->request->action) {
            case 'createform':
            case 'updateform':
                $this->request->addParam(array('cfg' => 'acp/products/product-var'));
        }

        $h = parent::route();
        return $h;
    }
}
