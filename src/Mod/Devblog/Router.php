<?php

namespace Mod\Devblog;

use Mod\News\Block\AsPage;
use Mod\Devblog;
use Mod\Routine\Block\MakeList;

class Router extends \Verba\Request\Http\Router
{

    function route()
    {
        if ($this->request->node == '') {

            $this->request->setOt('news');
            $this->request->setParent(\Verba\_oh('catalog')->getID(), Devblog::CATALOG_ROOT_ID);
            $h = new MakeList($this, ['cfg' => 'public public/devblog/index']);

        } elseif (is_numeric($this->request->node)) {

            $this->request->setIid($this->request->node);
            $h = new AsPage($this, [
                'cssClass' => ['devblog-show'],
            ]);
        }

        if (!isset($h)) {
            throw new \Exception\Routing();
        }

        $response = $h->route();
        return $response;
    }
}
