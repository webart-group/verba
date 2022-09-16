<?php

namespace Verba\Mod\Meta\Router;

class ACP extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->request->node) {
            case 'cuform':
                $h = new \Verba\Mod\Meta\Block\Form($this);
                break;
            case 'editnow':
            case 'newnow':
                $h = new \Verba\Mod\Meta\Block\AddEditNow($this);
                break;
            default:
                throw new \Verba\Exception\Routing();
        }
        return $h;
    }
}
