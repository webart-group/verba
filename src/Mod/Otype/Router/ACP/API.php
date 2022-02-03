<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 28.08.19
 * Time: 17:29
 */

namespace Mod\Otype\Router\ACP;


class API extends \Verba\Request\Http\Router
{
    function route()
    {
        switch($this->request->node){
            case 'attr':
                $h = (new API\Attribute($this->rq->shift()))->route();
                break;
            case 'props':
                $h = (new API\Props($this->request->shift()))->route();
                break;
        }

        if(!isset($h)){
            throw new \Exception\Routing();
        }

        return $h->route();
    }
}
