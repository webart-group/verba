<?php

namespace Verba\Mod\CfgModify\Router;

use Verba\Mod\CfgModify\Block\Form;
use Verba\Mod\CfgModify\Block\Save;

class ACP extends \Verba\Request\Http\Router
{
    function route()
    {
        switch (strtolower($this->request->node)) {
            case 'form':
                $h = new Form($this, array('modcode' => $this->request->uf[1]));
                break;
            case 'customize':
                $h = new Save($this, array('modcode' => $this->request->uf[1]));
                break;
            default:
                throw new \Verba\Exception\Routing();
        }

        return $h;
    }

}
