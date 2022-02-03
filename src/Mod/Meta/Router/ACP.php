<?php

namespace Mod\Meta\Router;

class ACP extends \Verba\Request\Http\Router
{

    function route()
    {
        switch ($this->request->node) {
            case 'cuform':
                $h = new \Mod\Meta\Block\Form($this);
                break;
            case 'editnow':
            case 'newnow':
                $h = new \Mod\Meta\Block\AddEditNow($this);
                break;
            default:
                throw new \Exception\Routing();
        }
        return $h;
    }
}
