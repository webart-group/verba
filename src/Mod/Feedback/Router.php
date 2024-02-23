<?php
namespace Verba\Mod\Feedback;

use Verba\Mod\Feedback\Block\AddEntry;

class Router extends \Verba\Request\Http\Router
{

    function route()
    {
        if ($this->request->node == 'add') {
            $h = new AddEntry($this->rq->shift());
        }

        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }

        $response = $h->route();
        return $response;
    }
}
