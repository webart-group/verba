<?php

namespace Verba\Mod\Acp\Router;

use \Verba\Mod\Acp\Block\Objectlinker\Search;

class Objectlinker extends \Verba\Block\Html
{

    function route()
    {
        switch ($this->request->node) {
            case 'search' :
                $h = new Search($this);
                break;
            default:
                throw new \Verba\Exception\Routing();
        }

        $response = $h->route();

        return $response;
    }

}
