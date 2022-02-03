<?php

namespace Mod\Currency\Router\ACP;

use Mod\Currency\Block\Cppr\Info;
use Mod\Currency\Block\Cppr\Run;
use Mod\Currency\Block\Cppr\Tab;

class Cppr extends \Verba\Request\Http\Router
{
    function route()
    {
        switch ($this->rq->node) {
            case 'info':
                $h = new Info($this->rq->shift());
                break;
            case 'runnow':
                $h = new Run($this->rq->shift());
                break;
            case '':
                $h = new Tab($this->rq);
                break;
            default:
                throw new \Exception\Routing();
        }
        return $h;
    }
}
