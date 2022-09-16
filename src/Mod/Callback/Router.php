<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 15:56
 */

namespace Verba\Mod\Callback;


class Router extends \Verba\Request\Http\Router
{
    function route(){
        $rq = clone $this->request;
        array_shift($rq->uf);
        switch($rq->uf[0]){
            case 'add':
                $h = new \Verba\Response\Json($this);
                $h->addItems(array(
                    new \callback_add($rq),
                ));
                break;
            default :
        }

        if(!isset($h)){
            throw new \Verba\Exception\Routing();
        }

        $r = $h->route();
        return $r;
    }
}
