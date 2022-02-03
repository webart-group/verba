<?php

namespace Mod\Currency\Router;

use Mod\ACP\Router\ObjectType;

class ACP extends \Verba\Request\Http\Router
{
    public $otcode = 'currency';

    function route()
    {

        switch ($this->rq->node) {
            case 'baseform':
                $router = new \Mod\Currency\Block\Base\Form($this);
                break;
            case 'savebasecurrencyid':
                $router = new \Mod\Currency\Block\Base\Save($this);
                break;
            case 'cppr':
                $router = new ACP\Cppr($this->rq->shift());
                break;
            case 'links':
                $router = (new ACP\Links($this->rq->shift()))->route();
                break;
        }

        if (!isset($router)) {
            $router = new ObjectType($this->rq);
        }

        $h = $router->route();

        return $h;
    }
}
