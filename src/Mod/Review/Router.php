<?php
namespace Verba\Mod\Review;

use Verba\Mod\Review\Block\ReviewAdd;

class Router extends \Verba\Request\Http\Router
{

    function route()
    {
        if ($this->request->node == 'add') {
            $h = new ReviewAdd($this->rq->shift());
        }

        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }

        $response = $h->route();
        return $response;
    }
}
