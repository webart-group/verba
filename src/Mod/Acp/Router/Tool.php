<?php

namespace Verba\Mod\Acp\Router;

class Tool extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->rq->node) {

            case 'account':
                $h = new \account_acpTools($this->rq->shift());
                break;

            case '':
                $h = new \Verba\Mod\Acp\Block\Tool\Tab($this);
                break;
        }

        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }

        return $h->route();
    }
}
