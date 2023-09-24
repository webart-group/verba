<?php
namespace Verba\Mod\Comment;

use Verba\Mod\Comment\Block\CommentAdd;

class Router extends \Verba\Request\Http\Router
{

    function route()
    {
        if ($this->request->node == 'add') {
            $h = new CommentAdd($this->rq->shift());
        }

        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }

        $response = $h->route();
        return $response;
    }
}
