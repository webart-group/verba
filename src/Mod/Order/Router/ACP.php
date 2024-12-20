<?php

namespace Verba\Mod\Order\Router;

use Verba\Mod\Acp\Router\Crud;

class ACP extends \Verba\Request\Http\Router
{

    function route()
    {
        $this->rq->setOt('order');

        if (!empty($this->request->action)) {
            switch ($this->request->action) {
                case 'transactions':
                    $router = new \Verba\Mod\Order\Transaction($this);
                    break;
            }
        }

        if (!isset($router)) {
            $router = new Crud($this->rq);
        }
        $h = $router->route();

        return $h;
    }
}
